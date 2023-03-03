<?php

namespace App\Entity;

use App\Repository\UserRepository;

use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Id]
    #[ORM\Column(length: 64)]
    private ?string $email = null;

    #[Assert\Length(min: 21, max:21)]
    #[ORM\Column(length: 21, nullable: true)]
    private ?string $session = null;

    #[ORM\Column(length: 60)]
    private ?string $password = null;

    #[Assert\NotBlank]
    #[Assert\Regex('/^[[:upper:]][[:lower:]]*((\b\s[[:alpha:]][[:lower:]]*)*(\b\s[[:upper:]][[:lower:]]*))*$/')]
    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[Assert\Range(min: 1, max: 99999)]
    #[ORM\Column]
    private ?int $zip = null;

    #[Assert\NotBlank]
    #[Assert\Regex('/^[[:upper:]][[:lower:]]*(\b\s[[:alpha:]][[:lower:]]*)*$/')]
    #[ORM\Column(length: 64)]
    private ?string $place = null;

    #[Assert\NotBlank]
    #[Assert\Regex('/^[[:digit:]]{9,12}$/')]
    #[ORM\Column(length: 15)]
    private ?string $phone = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name:"usergroup", referencedColumnName:"name", nullable: false)]
    private ?UserGroup $usergroup = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSession(): ?string
    {
        return $this->session;
    }

    public function setSession(?string $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getZip(): ?int
    {
        return $this->zip;
    }

    public function setZip(int $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(string $place): self
    {
        $this->place = $place;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getUsergroup(): ?UserGroup
    {
        return $this->usergroup;
    }

    public function setUsergroup(?UserGroup $usergroup): self
    {
        $this->usergroup = $usergroup;

        return $this;
    }
}
