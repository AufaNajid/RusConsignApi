<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\MessageSent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Chat;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio_desc',
        'mitra_id',
        'status_pembayaran',
        'image_profiles'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */

    protected $table = 'users';
    protected $guarded = ['id'];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed'
        ];
    }
    protected $primaryKey = 'id';

    const USER_TOKEN = 'userToken';

    public function mitra()
    {
        return $this->hasMany(Mitra::class, 'user_id', 'id');
    }

    public function profileImages()
    {
        return $this->hasMany(ProfileImage::class, 'user_id', 'id');
    }

    public function cods()
    {
        return $this->hasMany(Cod::class);
    }

    public function chat(): HasMany
    {
        return $this->hasMany(Chat::class,'created_by');
    }

    public function createNewToken($name, array $abilities = ['*'])
    {
        return $this->createToken($name, $abilities)->plainTextToken;
    }

    public function routeNotificationForOneSignal() : array{
        return ['tags'=>['key'=>'userId','relation'=>'=', 'value'=>(string)(1)]];
    }


    public function sendNowMessageNotification(array $data) : void {
        $this->notify(new MessageSent($data));
    }

    public function komentars()
    {
        return $this->hasMany(Komentar::class);
    }
}
