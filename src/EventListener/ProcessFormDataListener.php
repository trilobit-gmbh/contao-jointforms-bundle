<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\JointformsBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\Form;
use Contao\FormModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;
use Trilobit\JointformsBundle\Event\JointformsEvent;

/**
 * Class ProcessFormDataListener.
 *
 * @Hook("processFormData", priority=10)
 */
class ProcessFormDataListener extends ConfigurationProvider
{
    /**
     * @throws \Safe\Exceptions\JsonException
     * @throws \JsonException
     */
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function __invoke(array $submittedData, array $formData, ?array $files, array $labels, Form $form): void
    {
        $formId = (int) $formData['id'];
        $formKey = 'form'.$formData['id'];

        if (empty($form->jf_environment)) {
            return;
        }

        $jf = new ConfigurationProvider($form->jf_environment);
        $item = $jf->getElementByTypeAndId('tl_form', $formId);

        if (empty($item)) {
            return;
        }

        if (\is_array($files)
            && 1 === (int) $jf->config['member']->assignDir
            && !empty($homeDir = FilesModel::findByUuid($jf->config['member']->homeDir)->path) ? $homeDir : ''
        ) {
            foreach ($files as $key => $file) {
                if (isset($file['error']) && 0 === $file['error']) {
                    $parts = pathinfo($file['name']);

                    $extension = mb_convert_case($parts['extension'], \MB_CASE_LOWER);
                    $filename = mb_convert_case($parts['filename'], \MB_CASE_LOWER);

                    $name = $homeDir.'/'.$filename.'.'.$extension;

                    if (file_exists($file['tmp_name'])) {
                        if (file_exists($jf->rootDir.'/'.$name)) {
                            unlink($jf->rootDir.'/'.$name);
                        }

                        if (\array_key_exists('checkPdf', $jf->config)
                            && $jf->config['checkPdf']
                        ) {
                            system('pdf2ps "'.$file['tmp_name'].'" - | ps2pdf - "'.$jf->rootDir.'/'.$name.'"');
                        } elseif ($file['tmp_name'] !== $jf->rootDir.'/'.$name) {
                            copy($file['tmp_name'], $jf->rootDir.'/'.$name);
                        }
                    }

                    $submittedData[$key] = $filename.'.'.$extension;

                    try {
                        Dbafs::addResource($name);
                    } catch (\Exception $exception) {
                    }
                }
            }

            Dbafs::updateFolderHashes($homeDir);

            foreach ($files as $file) {
                if (file_exists($file['tmp_name'])) {
                    unlink($file['tmp_name']);
                }
            }
        }

        $json = (!empty($jf->config['member']->jf_data)
            ? html_entity_decode($jf->config['member']->jf_data)
            : ''
        );

        if (!empty($json)) {
            $json = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        } else {
            $json = new \stdClass();
        }

        $json->last_modified = time();

        foreach ($_POST as $key => $value) {
            if (preg_match('/^multi_form_size__\d+$/', $key)
                && isset($_REQUEST[$key])
            ) {
                $submittedData[$key] = (int) $_REQUEST[$key];
                continue;
            }
        }

        $submittedData['jointforms_complete'] = true;
        $submittedData['jointforms_complete_datim'] = $json->last_modified;

        $json->{$formKey} = new \stdClass();
        foreach ($submittedData as $key => $value) {
            $json->{$formKey}->{$key} = $value;
            $jf->config['jointforms']->{$formKey}->{$key} = $value;
        }

        if (!empty($item['submit'])) {
            $jf->config['member']->jf_complete = '1';
            $jf->config['member']->jf_complete_datim = $json->last_modified;
        }

        $jf->config['member']->jf_data = json_encode($json, \JSON_THROW_ON_ERROR);
        $jf->config['member']->jf_last_modified = $json->last_modified;
        $jf->config['member']->save();

        $event = new JointformsEvent($jf);
        $this->eventDispatcher->dispatch($event, JointformsEvent::JF_PROCESS_FORM);

        if (empty($item['submit'])) {
            $nextForm = $jf->getNextForm();

            $target = $jf->getUrl($jf->page);
            if (null !== ($model = FormModel::findById($nextForm))) {
                $target = $jf->getUrl($jf->page, $model->alias);
            }

            Controller::redirect($target);
        }
    }
}
