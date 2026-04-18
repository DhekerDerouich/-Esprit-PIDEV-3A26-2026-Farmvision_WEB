<?php
// src/Entity/Utilisateur.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'type_role', type: 'string')]
    private ?string $type_role = 'AGRICULTEUR';

    #[ORM\Column(name: 'nom', type: 'string', length: 100)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    private ?string $nom = null;

    #[ORM\Column(name: 'prenom', type: 'string', length: 100)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    private ?string $prenom = null;

    #[ORM\Column(name: 'email', type: 'string', length: 150, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide")]
    private ?string $email = null;

    #[ORM\Column(name: 'password', type: 'string', nullable: true, length: 255)]
    private ?string $password = null;

    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(name: 'activated', type: 'integer', nullable: true)]
    private ?int $activated = 1;

    #[ORM\Column(name: 'matricule', type: 'string', nullable: true, length: 50)]
    private ?string $matricule = null;

    #[ORM\Column(name: 'telephone', type: 'string', nullable: true, length: 20)]
    private ?string $telephone = null;

    #[ORM\Column(name: 'adresse', type: 'string', nullable: true, length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(name: 'remarques', type: 'string', nullable: true, length: 1000)]
    private ?string $remarques = null;

    public function __construct()
    {
        $this->date_creation = new \DateTime();
        $this->activated = 1;
        $this->type_role = 'AGRICULTEUR';
    }

    // GETTERS ET SETTERS
    public function getId(): ?int { return $this->id; }
    public function getTypeRole(): ?string { return $this->type_role; }
    public function setTypeRole(?string $type_role): self { $this->type_role = $type_role; return $this; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): self { $this->nom = $nom; return $this; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $prenom): self { $this->prenom = $prenom; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $password): self { $this->password = $password; return $this; }
    public function getDateCreation(): ?\DateTimeInterface { return $this->date_creation; }
    public function setDateCreation(?\DateTimeInterface $date_creation): self { $this->date_creation = $date_creation; return $this; }
    public function getActivated(): ?int { return $this->activated; }
    public function setActivated(?int $activated): self { $this->activated = $activated; return $this; }
    public function getMatricule(): ?string { return $this->matricule; }
    public function setMatricule(?string $matricule): self { $this->matricule = $matricule; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): self { $this->adresse = $adresse; return $this; }
    public function getRemarques(): ?string { return $this->remarques; }
    public function setRemarques(?string $remarques): self { $this->remarques = $remarques; return $this; }

    // Méthodes Security
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        switch($this->type_role) {
            case 'ADMINISTRATEUR': $roles[] = 'ROLE_ADMIN'; break;
            case 'RESPONSABLE_EXPLOITATION': $roles[] = 'ROLE_RESPONSABLE'; break;
            case 'AGRICULTEUR': $roles[] = 'ROLE_AGRICULTEUR'; break;
        }
        return array_unique($roles);
    }

    public function getUserIdentifier(): string { return $this->email; }
    public function eraseCredentials(): void {}
    public function isActivated(): bool { return $this->activated === 1; }
}