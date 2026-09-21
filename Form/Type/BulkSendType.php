<?php

declare(strict_types=1);

namespace MauticPlugin\AcolonoBulkMailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class BulkSendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', ChoiceType::class, [
            'label'       => 'mautic.bulkmailer.form.template',
            'choices'     => $options['email_choices'],
            'placeholder' => 'mautic.bulkmailer.form.template.placeholder',
            'required'    => true,
            'attr'        => ['class' => 'form-control'],
            'constraints' => [new NotBlank()],
        ]);

        $builder->add('file', FileType::class, [
            'label'       => 'mautic.bulkmailer.form.file',
            'mapped'      => false,
            'required'    => true,
            'attr'        => ['class' => 'form-control', 'accept' => '.csv,.xlsx,.ods'],
            'constraints' => [
                new NotBlank(),
                new File([
                    'maxSize'          => '8M',
                    'mimeTypes'        => [
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.oasis.opendocument.spreadsheet',
                    ],
                    'mimeTypesMessage' => 'mautic.bulkmailer.form.file.invalid',
                ]),
            ],
        ]);

        $builder->add('skipAlreadySent', CheckboxType::class, [
            'label'    => 'mautic.bulkmailer.form.skip_already_sent',
            'required' => false,
            'data'     => false,
            'attr'     => ['class' => 'form-check-input'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'email_choices' => [],
        ]);
        $resolver->setAllowedTypes('email_choices', 'array');
    }

    public function getBlockPrefix(): string
    {
        return 'acolono_bulk_send';
    }
}
