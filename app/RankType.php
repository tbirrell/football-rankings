<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RankType extends Model
{
  public $timestamps=false;

    public function ranks()
    {
      return $this->hasMany(Rank::class);
    }
  
}
