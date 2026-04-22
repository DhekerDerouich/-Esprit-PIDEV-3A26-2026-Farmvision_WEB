<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;

class UserAnalyticsService
{
    public function __construct(
        private readonly UtilisateurRepository $repository
    ) {}

    public function getStats(): array
    {
        return [
            'total'        => $this->repository->createQueryBuilder('u')->select('COUNT(u.id)')->getQuery()->getSingleScalarResult(),
            'admins'       => $this->repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :r')->setParameter('r', 'ADMINISTRATEUR')->getQuery()->getSingleScalarResult(),
            'responsables' => $this->repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :r')->setParameter('r', 'RESPONSABLE_EXPLOITATION')->getQuery()->getSingleScalarResult(),
            'agriculteurs' => $this->repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :r')->setParameter('r', 'AGRICULTEUR')->getQuery()->getSingleScalarResult(),
            'actifs'       => $this->repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.activated = 1')->getQuery()->getSingleScalarResult(),
            'inactifs'     => $this->repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.activated = 0')->getQuery()->getSingleScalarResult(),
            'bannis'       => $this->repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.banStatus IS NOT NULL')->getQuery()->getSingleScalarResult(),
        ];
    }

    public function getIAStats(): array
    {
        $allUsers = $this->repository->findAll();

        return [
            'inscriptionsMois' => $this->getInscriptionsByMonth($allUsers),
            'genres'          => $this->getGenresStats($allUsers),
            'tranchesAge'    => $this->getAgeTranches($allUsers),
            'predictions'    => $this->getPredictions($allUsers),
            'croissance'    => $this->getCroissance($allUsers),
            'totalUsers'    => count($allUsers),
        ];
    }

    private function getInscriptionsByMonth(array $users): array
    {
        $inscriptions = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois = (new \DateTime())->modify("-{$i} months");
            $inscriptions[$mois->format('M Y')] = 0;
        }
        foreach ($users as $user) {
            if ($user->getDateCreation()) {
                $key = $user->getDateCreation()->format('M Y');
                if (isset($inscriptions[$key])) {
                    $inscriptions[$key]++;
                }
            }
        }
        return $inscriptions;
    }

    private function getGenresStats(array $users): array
    {
        $genres = ['M' => 0, 'F' => 0, 'A' => 0, 'NC' => 0];
        foreach ($users as $u) {
            $g = $u->getGenre();
            if ($g === 'M') $genres['M']++;
            elseif ($g === 'F') $genres['F']++;
            elseif ($g === 'A') $genres['A']++;
            else $genres['NC']++;
        }
        return $genres;
    }

    private function getAgeTranches(array $users): array
    {
        $tranches = ['<18' => 0, '18-25' => 0, '26-35' => 0, '36-50' => 0, '51-65' => 0, '>65' => 0, 'NC' => 0];
        foreach ($users as $u) {
            $age = $u->getAge();
            if ($age === null) { $tranches['NC']++; continue; }
            if ($age < 18)        $tranches['<18']++;
            elseif ($age <= 25)   $tranches['18-25']++;
            elseif ($age <= 35)   $tranches['26-35']++;
            elseif ($age <= 50)   $tranches['36-50']++;
            elseif ($age <= 65)   $tranches['51-65']++;
            else                  $tranches['>65']++;
        }
        return $tranches;
    }

    public function getPredictions(array $users): array
    {
        $inscriptions = $this->getInscriptionsByMonth($users);
        $valeurs = array_values($inscriptions);
        $n = count($valeurs);

        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumX  += $i;
            $sumY  += $valeurs[$i];
            $sumXY += $i * $valeurs[$i];
            $sumX2 += $i * $i;
        }

        $denom = ($n * $sumX2 - $sumX * $sumX);
        if ($denom !== 0) {
            $slope = ($n * $sumXY - $sumX * $sumY) / $denom;
            $intercept = ($sumY - $slope * $sumX) / $n;
        } else {
            $slope = 0;
            $intercept = $sumY / max(1, $n);
        }

        $predictions = [];
        for ($i = 0; $i < 6; $i++) {
            $mois = (new \DateTime())->modify('+' . ($i + 1) . ' months');
            $pred = max(0, round($slope * ($n + $i) + $intercept));
            $predictions[$mois->format('M Y')] = $pred;
        }

        return $predictions;
    }

    private function getCroissance(array $users): ?float
    {
        $inscriptions = $this->getInscriptionsByMonth($users);
        $valeurs = array_values($inscriptions);
        $n = count($valeurs);

        if ($n >= 2 && $valeurs[$n - 2] > 0) {
            return round((($valeurs[$n - 1] - $valeurs[$n - 2]) / $valeurs[$n - 2]) * 100, 1);
        }
        return null;
    }
}