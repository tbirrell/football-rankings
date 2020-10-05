<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use \Awobaz\Compoships\Compoships;

    public $timestamps = false;

    public function home_games()
    {
      return $this->hasMany(Game::class, 'home_team_id');
    }
    public function away_games()
    {
      return $this->hasMany(Game::class, 'away_team_id');
    }
    public function games()
    {
      return $this->eitherOr()->hasMany(Game::class, ['home_team_id','away_team_id'], ['id','id']);
    }

    public function ranks()
    {
      return $this->hasMany(Rank::class);
    }

    //=== Record ===//
    public function getWinsAttribute() {
      if (!array_key_exists('wins', $this->attributes))  $this->findRecord();
      return $this->attributes['wins'];
    }
    public function getLossesAttribute() {
      if (!array_key_exists('losses', $this->attributes))  $this->findRecord();
      return $this->attributes['losses'];
    } 
    public function setWinsAttribute($value) {
      $this->attributes['wins'] = $value;
    }
    public function setLossesAttribute($value) {
      $this->attributes['losses'] = $value;
    }    
    public function getConfWinsAttribute() {
      if (!array_key_exists('conf_wins', $this->attributes))  $this->findRecord();
      return $this->attributes['conf_wins'];
    }
    public function getConfLossesAttribute() {
      if (!array_key_exists('conf_losses', $this->attributes))  $this->findRecord();
      return $this->attributes['conf_losses'];
    } 
    public function setConfWinsAttribute($value) {
      $this->attributes['conf_wins'] = $value;
    }
    public function setConfLossesAttribute($value) {
      $this->attributes['conf_losses'] = $value;
    }
    public function findRecord() {
      $this->wins = 0;
      $this->losses = 0;
      $this->conf_wins = 0;
      $this->conf_losses = 0;
      foreach ($this->games as $game) {
        if ($game->winner->id === $this->id) {
          if ($game->confrence_game) {
            $this->attributes['conf_wins']++;
          }
          $this->attributes['wins']++;
        } else {
          if ($game->confrence_game) {
            $this->attributes['conf_losses']++;
          }
          $this->attributes['losses']++;
        }
      }
    }

    //=== Sort ===//

    public function getSortAttribute() {
      return $this->attributes['sort'];
    }
    public function setSortAttribute($value) {
      $this->attributes['sort'] = $value;
    }
}
