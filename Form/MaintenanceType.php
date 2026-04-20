<?php
namespace App\Form;

use App\Entity\Maintenance;
use App\Entity\Equipement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaintenanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('equipement', EntityType::class, [
                'class' => Equipement::class,
                'choice_label' => 'nom',
                'attr' => ['class' => 'form-control'],
                'label' => 'Équipement'
            ])
            ->add('typeMaintenance', ChoiceType::class, [
                'choices' => [
                    'Préventive' => 'Préventive',
                    'Corrective' => 'Corrective',
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Type de maintenance'
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false,
                'label' => 'Description'
            ])
            ->add('dateMaintenance', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Date de maintenance'
            ])
            ->add('cout', NumberType::class, [
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
                'required' => false,
                'label' => 'Coût (DT)'
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Planifiée' => 'Planifiée',
                    'Réalisée' => 'Réalisée',
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Statut'
            ]);
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Maintenance::class,
        ]);
    }
}