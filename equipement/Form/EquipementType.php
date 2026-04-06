<?php
namespace App\Form;

use App\Entity\Equipement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom de l\'équipement'],
                'label' => 'Nom'
            ])
            ->add('type', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Type (Tracteur, Moissonneuse, etc.)'],
                'label' => 'Type'
            ])
            ->add('etat', ChoiceType::class, [
                'choices' => [
                    'Fonctionnel' => 'Fonctionnel',
                    'En panne' => 'En panne',
                    'Maintenance' => 'Maintenance',
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'État'
            ])
            ->add('dateAchat', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'label' => 'Date d\'achat'
            ])
            ->add('dureeVieEstimee', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Durée de vie en années'],
                'required' => false,
                'label' => 'Durée de vie estimée (années)'
            ]);
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipement::class,
        ]);
    }
}