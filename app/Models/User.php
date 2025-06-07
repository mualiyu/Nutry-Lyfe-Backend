<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'phone',
        'user_type',
        'status',
        'isActive',
        'photo',
        'description',
        'my_ref_id',
        'ref_id',
        'acct_name',
        'acct_number',
        'acct_type',
        'bank',
        'state',
        'lga',
        'address',
        'package_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
        ];
    }


   /**
     * Get all of the comments for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'ref_id', 'my_ref_id');
    }

    public function downline()
    {
        return $this->hasMany(User::class, 'ref_id', 'my_ref_id');
    }

    public function allDownline()
    {
        return $this->downline()->with('allDownline');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ref_id', 'my_ref_id');
    }

     /**
     * Get all upline referrers up to 10 generations
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function upline()
    {
        return $this->referrer()->with([
            'referrer.referrer.referrer.referrer.referrer.referrer.referrer.referrer.referrer.referrer'
        ]);
    }

    // Alternative method if you want to get them as a collection
    public function getUplineUsers($levels = 10)
    {
        $uplineUsers = collect();
        $currentUser = $this;

        for ($i = 0; $i < $levels; $i++) {
            $referrer = $currentUser->referrer;
            if (!$referrer) {
                break;
            }
            $uplineUsers->push($referrer);
            $currentUser = $referrer;
        }

        return $uplineUsers;
    }



    public function wallet() : HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get user's products with eager loading and specific fields
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userProducts(): HasMany
    {
        return $this->hasMany(UserProduct::class, 'user_id', 'id');
    }

    public function products() : BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'user_products')->withTimestamps();
    }

    public function bonuses()
    {
        return $this->hasMany(Bonus::class);
    }

    public function genealogy()
    {
        return $this->hasOne(Genealogy::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // public function userPackage()
    // {
    //     return $this->hasOne(UserPackage::class);
    // }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function ranking()
    {
        return $this->hasOne(Ranking::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

}
