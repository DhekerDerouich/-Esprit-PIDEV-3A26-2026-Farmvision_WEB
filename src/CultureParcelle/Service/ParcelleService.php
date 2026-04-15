<?php

namespace App\CultureParcelle\Service;

use App\Entity\Parcelle;
use App\CultureParcelle\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ParcelleService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private ParcelleRepository $repository
    ) {}

    /**
     * Create a new parcelle from data
     */
    public function createParcelle(array $data, ?int $userId = null): array
    {
        $parcelle = new Parcelle();
        $this->populateParcelle($parcelle, $data, $userId);
        
        $errors = $this->validateParcelle($parcelle);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $this->em->persist($parcelle);
        $this->em->flush();
        
        return ['success' => true, 'parcelle' => $parcelle];
    }

    /**
     * Update an existing parcelle
     */
    public function updateParcelle(Parcelle $parcelle, array $data): array
    {
        $this->populateParcelle($parcelle, $data);
        
        $errors = $this->validateParcelle($parcelle);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $this->em->flush();
        
        return ['success' => true, 'parcelle' => $parcelle];
    }

    /**
     * Delete a parcelle
     */
    public function deleteParcelle(Parcelle $parcelle): void
    {
        $this->em->remove($parcelle);
        $this->em->flush();
    }

    /**
     * Search parcelles with filters
     */
    public function searchParcelles(?string $localisation, ?float $surfaceMin, ?float $surfaceMax, ?int $userId): array
    {
        return $this->repository->search($localisation, $surfaceMin, $surfaceMax, $userId);
    }

    /**
     * Populate parcelle entity from data array
     */
    private function populateParcelle(Parcelle $parcelle, array $data, ?int $userId = null): void
    {
        if (isset($data['localisation'])) {
            $parcelle->setLocalisation(trim($data['localisation']));
        }
        
        if (isset($data['surface'])) {
            $parcelle->setSurface($data['surface'] !== '' ? (float)$data['surface'] : null);
        }
        
        if (isset($data['latitude'])) {
            $parcelle->setLatitude($data['latitude'] !== '' ? (float)$data['latitude'] : null);
        }
        
        if (isset($data['longitude'])) {
            $parcelle->setLongitude($data['longitude'] !== '' ? (float)$data['longitude'] : null);
        }
        
        if ($userId !== null) {
            $parcelle->setUserId($userId);
        }
    }

    /**
     * Validate parcelle and return errors
     */
    private function validateParcelle(Parcelle $parcelle): array
    {
        $violations = $this->validator->validate($parcelle);
        $errors = [];
        
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }
        
        return $errors;
    }
}
