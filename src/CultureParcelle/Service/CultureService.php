<?php

namespace App\CultureParcelle\Service;

use App\Entity\Culture;
use App\CultureParcelle\Repository\CultureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CultureService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private CultureRepository $repository
    ) {}

    /**
     * Create a new culture from data
     */
    public function createCulture(array $data, ?int $userId = null): array
    {
        $culture = new Culture();
        $this->populateCulture($culture, $data, $userId);
        
        $errors = $this->validateCulture($culture);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $this->em->persist($culture);
        $this->em->flush();
        
        return ['success' => true, 'culture' => $culture];
    }

    /**
     * Update an existing culture
     */
    public function updateCulture(Culture $culture, array $data): array
    {
        $this->populateCulture($culture, $data);
        
        $errors = $this->validateCulture($culture);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $this->em->flush();
        
        return ['success' => true, 'culture' => $culture];
    }

    /**
     * Delete a culture
     */
    public function deleteCulture(Culture $culture): void
    {
        $this->em->remove($culture);
        $this->em->flush();
    }

    /**
     * Update culture date (for calendar drag & drop)
     */
    public function updateCultureDate(Culture $culture, string $type, \DateTime $newDate): void
    {
        if ($type === 'semis') {
            $culture->setDateSemis($newDate);
        } else {
            $culture->setDateRecolte($newDate);
        }
        
        $this->em->flush();
    }

    /**
     * Get cultures for a specific user
     */
    public function getUserCultures(?string $search, string $type, ?int $userId): array
    {
        return $this->repository->search($search, $type, $userId);
    }

    /**
     * Get all culture types
     */
    public function getAllTypes(): array
    {
        return $this->repository->findAllTypes();
    }

    /**
     * Populate culture entity from data array
     */
    private function populateCulture(Culture $culture, array $data, ?int $userId = null): void
    {
        if (isset($data['nomCulture'])) {
            $culture->setNomCulture(trim($data['nomCulture']));
        }
        
        if (isset($data['typeCulture'])) {
            $culture->setTypeCulture(trim($data['typeCulture']));
        }
        
        if (isset($data['dateSemis'])) {
            $culture->setDateSemis($data['dateSemis'] ? new \DateTime($data['dateSemis']) : null);
        }
        
        if (isset($data['dateRecolte'])) {
            $culture->setDateRecolte($data['dateRecolte'] ? new \DateTime($data['dateRecolte']) : null);
        }
        
        if ($userId !== null) {
            $culture->setUserId($userId);
        }
    }

    /**
     * Validate culture and return errors
     */
    private function validateCulture(Culture $culture): array
    {
        $violations = $this->validator->validate($culture);
        $errors = [];
        
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }
        
        return $errors;
    }
}
