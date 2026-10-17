<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\InstituteResetPasswordNotification;
class User extends Authenticatable {
 use Notifiable;
 protected $fillable=['name','email','password','role'];
 protected $hidden=['password','remember_token'];
 protected $casts=['password'=>'hashed'];
 public function sendPasswordResetNotification($token): void { $this->notify(new InstituteResetPasswordNotification($token)); }
 public function isOperator(): bool { return in_array($this->role,['operator','admin'],true); }
 public function isAdmin(): bool { return $this->role==='admin'; }
}