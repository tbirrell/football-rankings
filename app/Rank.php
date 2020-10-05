<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    public function team() {
      return $this->belongsTo(Team::class);
    }
    public function type(){
      return $this->belongsTo(RankType::class);
    }
}
