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
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
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

    #[ORM\Column(name: 'photo_profil', type: 'string', length: 255, nullable: true)]
    private ?string $photoProfil = null;

    #[ORM\Column(name: 'google_id', type: 'string', length: 255, nullable: true, unique: true)]
    private ?string $googleId = null;

    #[ORM\Column(name: 'reset_password_token', type: 'string', length: 255, nullable: true, unique: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(name: 'reset_password_expires_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetPasswordExpiresAt = null;

    // =============================================
    // 2FA - Authentification à deux facteurs
    // =============================================
    #[ORM\Column(name: 'two_factor_code', type: 'string', length: 6, nullable: true)]
    private ?string $twoFactorCode = null;

    #[ORM\Column(name: 'two_factor_expires_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $twoFactorExpiresAt = null;

    #[ORM\Column(name: 'two_factor_enabled', type: 'boolean')]
    private bool $twoFactorEnabled = false;

    // =============================================
    // NOUVEAUX CHAMPS — Ban système
    // =============================================

    /** NULL = pas banni, 'temporary' = durée limitée, 'permanent' = à vie */
    #[ORM\Column(name: 'ban_status', type: 'string', length: 20, nullable: true)]
    private ?string $banStatus = null;

    /** Raison affichée à l'utilisateur lors de la connexion */
    #[ORM\Column(name: 'ban_reason', type: 'string', length: 500, nullable: true)]
    private ?string $banReason = null;

    /** NULL = ban permanent, sinon date d'expiration du ban */
    #[ORM\Column(name: 'ban_expires_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $banExpiresAt = null;

    /** Date/heure où le ban a été appliqué */
    #[ORM\Column(name: 'banned_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $bannedAt = null;

    // =============================================
    // NOUVEAUX CHAMPS — Stats IA
    // =============================================

    #[ORM\Column(name: 'date_naissance', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    /** M = Masculin, F = Féminin, A = Autre */
    #[ORM\Column(name: 'genre', type: 'string', length: 1, nullable: true)]
    private ?string $genre = null;

    // =============================================

    public function __construct()
    {
        $this->date_creation = new \DateTime();
        $this->activated     = 1;
        $this->type_role     = 'AGRICULTEUR';
    }

    // --- Getters / Setters existants ---
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
    public function getPhotoProfil(): ?string { return $this->photoProfil; }
    public function setPhotoProfil(?string $photoProfil): self { $this->photoProfil = $photoProfil; return $this; }
    public function getGoogleId(): ?string { return $this->googleId; }
    public function setGoogleId(?string $googleId): self { $this->googleId = $googleId; return $this; }
    public function getResetPasswordToken(): ?string { return $this->resetPasswordToken; }
    public function setResetPasswordToken(?string $t): self { $this->resetPasswordToken = $t; return $this; }
    public function getResetPasswordExpiresAt(): ?\DateTimeImmutable { return $this->resetPasswordExpiresAt; }
    public function setResetPasswordExpiresAt(?\DateTimeImmutable $d): self { $this->resetPasswordExpiresAt = $d; return $this; }

    // --- Getters / Setters 2FA ---
    public function getTwoFactorCode(): ?string { return $this->twoFactorCode; }
    public function setTwoFactorCode(?string $code): self { $this->twoFactorCode = $code; return $this; }
    public function getTwoFactorExpiresAt(): ?\DateTimeImmutable { return $this->twoFactorExpiresAt; }
    public function setTwoFactorExpiresAt(?\DateTimeImmutable $d): self { $this->twoFactorExpiresAt = $d; return $this; }
    public function isTwoFactorEnabled(): bool { return $this->twoFactorEnabled; }
    public function setTwoFactorEnabled(bool $enabled): self { $this->twoFactorEnabled = $enabled; return $this; }

    // --- Getters / Setters ban ---
    public function getBanStatus(): ?string { return $this->banStatus; }
    public function setBanStatus(?string $banStatus): self { $this->banStatus = $banStatus; return $this; }
    public function getBanReason(): ?string { return $this->banReason; }
    public function setBanReason(?string $banReason): self { $this->banReason = $banReason; return $this; }
    public function getBanExpiresAt(): ?\DateTimeImmutable { return $this->banExpiresAt; }
    public function setBanExpiresAt(?\DateTimeImmutable $banExpiresAt): self { $this->banExpiresAt = $banExpiresAt; return $this; }
    public function getBannedAt(): ?\DateTimeImmutable { return $this->bannedAt; }
    public function setBannedAt(?\DateTimeImmutable $bannedAt): self { $this->bannedAt = $bannedAt; return $this; }

    // --- Getters / Setters stats ---
    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self { $this->dateNaissance = $dateNaissance; return $this; }
    public function getGenre(): ?string { return $this->genre; }
    public function setGenre(?string $genre): self { $this->genre = $genre; return $this; }

    // --- Méthodes métier ban ---

    /** Vérifie si l'utilisateur est actuellement banni (permanent ou temporaire non expiré) */
    public function isBanned(): bool
    {
        if ($this->banStatus === null) {
            return false;
        }
        if ($this->banStatus === 'permanent') {
            return true;
        }
        // Ban temporaire : vérifie l'expiration
        if ($this->banStatus === 'temporary' && $this->banExpiresAt !== null) {
            return $this->banExpiresAt > new \DateTimeImmutable();
        }
        return false;
    }

    /** Calcule l'âge à partir de dateNaissance */
    public function getAge(): ?int
    {
        if (!$this->dateNaissance) {
            return null;
        }
        return (new \DateTime())->diff($this->dateNaissance)->y;
    }

    // --- Méthodes Security ---
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        switch ($this->type_role) {
            case 'ADMINISTRATEUR':          $roles[] = 'ROLE_ADMIN'; break;
            case 'RESPONSABLE_EXPLOITATION': $roles[] = 'ROLE_RESPONSABLE'; break;
            case 'AGRICULTEUR':             $roles[] = 'ROLE_AGRICULTEUR'; break;
        }
        return array_unique($roles);
    }

    public function getUserIdentifier(): string { return $this->email; }
    public function eraseCredentials(): void {}
    public function isActivated(): bool { return $this->activated === 1; }
}