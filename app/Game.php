<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use \Awobaz\Compoships\Compoships;

  protected $casts = [
        // 'date' => 'date',
    ];
    public function home_team()
    {
      return $this->belongsTo(Team::class, 'home_team_id');
    }
    public function away_team()
    {
      return $this->belongsTo(Team::class, 'away_team_id');
    }
    // public function team()
    // {
    //   return $this->eitherOr()->belongsTo(Team::class, ['home_team_id','away_team_id'], ['id','id']);
    // }
    public function getOpponent($team) {
        return ($team->id === $this->home_team->id) ? $this->away_team : $this->home_team;
    }

    public function getConfrenceGameAttribute() {
        return ($this->home_team->conference === $this->away_team->conference);
    }

    public function getWinnerAttribute(){
        if ($this->home_score > $this->away_score) {
            return $this->home_team;
        } else {
            return $this->away_team;
        }
    }
    public function getWinningScoreAttribute(){
        return max($this->home_score, $this->away_score);
    }
    public function getLoserAttribute(){
        if ($this->home_score < $this->away_score) {
            return $this->home_team;
        } else {
            return $this->away_team;
        }
    }
    public function getLosingScoreAttribute(){
        return min($this->home_score, $this->away_score);
        
    }
}
