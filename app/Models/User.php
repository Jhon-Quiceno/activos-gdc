<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'perfil', 'activo', 'debe_cambiar_contrasena'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'debe_cambiar_contrasena' => 'boolean',
        ];
    }

    public function esAdministrador(): bool
    {
        return $this->perfil === 'administrador';
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class, 'usuario_id');
    }

    public function importaciones(): HasMany
    {
        return $this->hasMany(Importacion::class, 'usuario_id');
    }

    public function etiquetasQr(): HasMany
    {
        return $this->hasMany(EtiquetaQr::class, 'usuario_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }
}
