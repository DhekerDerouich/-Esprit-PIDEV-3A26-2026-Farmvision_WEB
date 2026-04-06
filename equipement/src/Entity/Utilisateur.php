<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'type_role', type: 'string')]
    private ?string $type_role;

    #[ORM\Column(name: 'nom', type: 'string', length: 100)]
    private ?string $nom;

    #[ORM\Column(name: 'prenom', type: 'string', length: 100)]
    private ?string $prenom;

    #[ORM\Column(name: 'email', type: 'string', length: 150)]
    private ?string $email;

    #[ORM\Column(name: 'password', type: 'string', nullable: true, length: 255)]
    private ?string $password;

    #[ORM\Column(name: 'date_creation', type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_creation;

    #[ORM\Column(name: 'activated', type: 'integer', nullable: true, length: 1)]
    private ?int $activated;

    #[ORM\Column(name: 'matricule', type: 'string', nullable: true, length: 50)]
    private ?string $matricule;

    #[ORM\Column(name: 'telephone', type: 'string', nullable: true, length: 20)]
    private ?string $telephone;

    #[ORM\Column(name: 'adresse', type: 'string', nullable: true, length: 255)]
    private ?string $adresse;

    #[ORM\Column(name: 'remarques', type: 'string', nullable: true, length: 1000)]
    private ?string $remarques;

    public function __construct()
    {
        $this->parcelles = new ArrayCollection();
        $this->cultures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeRole(): ?string
    {
        return $this->type_role;
    }

    public function setTypeRole(?string $type_role): self
    {
        $this->type_role = $type_role;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(?\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getActivated(): ?int
    {
        return $this->activated;
    }

    public function setActivated(?int $activated): self
    {
        $this->activated = $activated;
        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(?string $matricule): self
    {
        $this->matricule = $matricule;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getRemarques(): ?string
    {
        return $this->remarques;
    }

    public function setRemarques(?string $remarques): self
    {
        $this->remarques = $remarques;
        return $this;
    }

}
