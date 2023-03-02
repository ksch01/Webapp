<?php

namespace App\Entity;

use App\Repository\UserGroupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserGroupRepository::class)]
class UserGroup
{
    #[ORM\Id]
    #[ORM\Column(length: 16)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $priv_login = null;

    #[ORM\Column]
    private ?bool $priv_delete = null;

    #[ORM\Column]
    private ?bool $edit_own_cred = null;

    #[ORM\Column]
    private ?bool $edit_own_pass = null;

    #[ORM\Column]
    private ?bool $edit_own_priv = null;

    #[ORM\Column]
    private ?bool $edit_oth_cred = null;

    #[ORM\Column]
    private ?bool $edit_oth_pass = null;

    #[ORM\Column]
    private ?bool $edit_oth_priv = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isPrivLogin(): ?bool
    {
        return $this->priv_login;
    }

    public function setPrivLogin(bool $priv_login): self
    {
        $this->priv_login = $priv_login;

        return $this;
    }

    public function isPrivDelete(): ?bool
    {
        return $this->priv_delete;
    }

    public function setPrivDelete(bool $priv_delete): self
    {
        $this->priv_delete = $priv_delete;

        return $this;
    }

    public function isEditOwnCred(): ?bool
    {
        return $this->edit_own_cred;
    }

    public function setEditOwnCred(bool $edit_own_cred): self
    {
        $this->edit_own_cred = $edit_own_cred;

        return $this;
    }

    public function isEditOwnPass(): ?bool
    {
        return $this->edit_own_pass;
    }

    public function setEditOwnPass(bool $edit_own_pass): self
    {
        $this->edit_own_pass = $edit_own_pass;

        return $this;
    }

    public function isEditOwnPriv(): ?bool
    {
        return $this->edit_own_priv;
    }

    public function setEditOwnPriv(bool $edit_own_priv): self
    {
        $this->edit_own_priv = $edit_own_priv;

        return $this;
    }

    public function isEditOthCred(): ?bool
    {
        return $this->edit_oth_cred;
    }

    public function setEditOthCred(bool $edit_oth_cred): self
    {
        $this->edit_oth_cred = $edit_oth_cred;

        return $this;
    }

    public function isEditOthPass(): ?bool
    {
        return $this->edit_oth_pass;
    }

    public function setEditOthPass(bool $edit_oth_pass): self
    {
        $this->edit_oth_pass = $edit_oth_pass;

        return $this;
    }

    public function isEditOthPriv(): ?bool
    {
        return $this->edit_oth_priv;
    }

    public function setEditOthPriv(bool $edit_oth_priv): self
    {
        $this->edit_oth_priv = $edit_oth_priv;

        return $this;
    }
}
