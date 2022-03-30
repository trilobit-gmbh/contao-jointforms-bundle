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
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * Class ProcessFormDataListener.
 *
 * @Hook("processFormData", priority=10)
 */
class ProcessFormDataListener
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
                        if ($jf->config['checkPdf']) {
                            system('pdf2ps "'.$file['tmp_name'].'" - | ps2pdf - "'.$jf->rootDir.'/'.$name.'"');
                        } elseif ($file['tmp_name'] !== $jf->rootDir.'/'.$name) {
                            rename($file['tmp_name'], $jf->rootDir.'/'.$name);
                        }
                    }

                    $submittedData[$key] = $key.'.'.$extension;

                    Dbafs::addResource($name);
                }
            }
        }

        $json = html_entity_decode($jf->config['member']->jf_data);

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

        $submittedData['jointforms_complete'] = true;

        $json->{$formKey} = $submittedData;

        $json->last_modified = time();

        if (!empty($item['submit'])) {
            $jf->config['member']->jf_complete = '1';
            $jf->config['member']->jf_complete_datim = $json->last_modified;
        }

        $jf->config['member']->jf_data = json_encode($json, \JSON_THROW_ON_ERROR);
        $jf->config['member']->save();

        if (empty($item['submit'])) {
            Controller::redirect($jf->getUrl($jf->page));
        }
    }
}
