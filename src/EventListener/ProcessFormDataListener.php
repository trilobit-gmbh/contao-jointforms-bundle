<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-jointforms-bundle
 */

namespace Trilobit\JointformsBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\Form;
use Contao\FormModel;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

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
            && '1' === $jf->config['member']->assignDir
            && !empty($homeDir = FilesModel::findByUuid($jf->config['member']->homeDir)->path) ? $homeDir : ''
        ) {
            foreach ($files as $key => $file) {
                if (0 === $file['error']) {
                    $parts = pathinfo($file['name']);

                    $extension = mb_convert_case($parts['extension'], \MB_CASE_LOWER);
                    $name = $homeDir.'/'.$key.'.'.$extension;

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

                    $submittedData[$key] = $key.'.'.$extension;

                    Dbafs::addResource($name);
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
            try {
                $config['jointforms'] = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                $config['jointforms'] = new \stdClass();
            }
        }

        if (!empty($json)) {
            $json = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        } else {
            $json = new \stdClass();
        }

        $json->last_modified = time();

        $submittedData['jointforms_complete'] = true;
        $submittedData['jointforms_complete_datim'] = $json->last_modified;

        $json->{$formKey} = new \stdClass();
        foreach ($submittedData as $key => $value) {
            $json->{$formKey}->{$key} = $value;
        }

        if (!empty($item['submit'])) {
            $jf->config['member']->jf_complete = '1';
            $jf->config['member']->jf_complete_datim = $json->last_modified;
        }

        $jf->config['member']->jf_data = json_encode($json, \JSON_THROW_ON_ERROR);
        $jf->config['member']->jf_last_modified = $json->last_modified;
        $jf->config['member']->save();

        if (empty($item['submit'])) {
            /*
            //var_dump($json);

            $currentForm = $jf->getCurrentForm();
            if (null !== ($model = FormModel::findByIdOrAlias($currentForm))) {
                var_dump([
                    '1',
                    $currentForm,
                    $model->alias,
                    $model->id
                ]);
            }

            $nextForm = $jf->getNextForm();
            if (null !== ($model = FormModel::findByIdOrAlias($nextForm))) {
                var_dump([
                    '2',
                    $nextForm,
                    $model->alias,
                    $model->id
                ]);
            }
            die();
            */

            $currentForm = $jf->getNextForm();

            $target = $jf->getUrl($jf->page);
            if (null !== ($model = FormModel::findById($currentForm))) {
                $target = $jf->getUrl($jf->page, $model->alias);
            }

            Controller::redirect($target);
        }
    }
}
