<?php

namespace App\Entity;

use App\Repository\SocialConnectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SocialConnectionRepository::class)]
class SocialConnection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $provider = null; // google, facebook, instagram, twitter

    #[ORM\Column(length: 255)]
    private ?string $provider_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $provider_email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $provider_name = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $profile_picture_url = null;

    #[ORM\Column]
    private ?bool $share_stats = false;

    #[ORM\Column]
    private ?bool $auto_post = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $connected_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $last_sync = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getProviderId(): ?string
    {
        return $this->provider_id;
    }

    public function setProviderId(string $provider_id): static
    {
        $this->provider_id = $provider_id;
        return $this;
    }

    public function getProviderEmail(): ?string
    {
        return $this->provider_email;
    }

    public function setProviderEmail(?string $provider_email): static
    {
        $this->provider_email = $provider_email;
        return $this;
    }

    public function getProviderName(): ?string
    {
        return $this->provider_name;
    }

    public function setProviderName(?string $provider_name): static
    {
        $this->provider_name = $provider_name;
        return $this;
    }

    public function getProfilePictureUrl(): ?string
    {
        return $this->profile_picture_url;
    }

    public function setProfilePictureUrl(?string $profile_picture_url): static
    {
        $this->profile_picture_url = $profile_picture_url;
        return $this;
    }

    public function isShareStats(): ?bool
    {
        return $this->share_stats;
    }

    public function setShareStats(bool $share_stats): static
    {
        $this->share_stats = $share_stats;
        return $this;
    }

    public function isAutoPost(): ?bool
    {
        return $this->auto_post;
    }

    public function setAutoPost(bool $auto_post): static
    {
        $this->auto_post = $auto_post;
        return $this;
    }

    public function getConnectedAt(): ?\DateTimeImmutable
    {
        return $this->connected_at;
    }

    public function setConnectedAt(?\DateTimeImmutable $connected_at): static
    {
        $this->connected_at = $connected_at;
        return $this;
    }

    public function getLastSync(): ?\DateTimeImmutable
    {
        return $this->last_sync;
    }

    public function setLastSync(?\DateTimeImmutable $last_sync): static
    {
        $this->last_sync = $last_sync;
        return $this;
    }
}
