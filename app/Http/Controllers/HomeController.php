<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function ranks()
    {

  $teams = DB::select("SELECT ANY_VALUE(school) as school, COALESCE(GROUP_CONCAT(name)) as name, COALESCE(GROUP_CONCAT(conference)) as conference, SUM(wins) as wins, SUM(losses) as losses, SUM(opp_wins) as opp_wins, SUM(opp_losses) as opp_losses, SUM(conf_wins) as conf_wins, SUM(conf_losses) as conf_losses
FROM
(SELECT ANY_VALUE(t.school) as school, null as name, null as conference, null as wins, null as losses, null as conf_wins, null as conf_losses,
SUM(IF((o.id = og.home_team_id and og.home_score > og.away_score) or (o.id = og.away_team_id and og.home_score < og.away_score), 1, 0)) as opp_wins, 
SUM(IF((o.id = og.home_team_id and og.home_score < og.away_score) or (o.id = og.away_team_id and og.home_score > og.away_score), 1, 0)) as opp_losses
FROM teams t
LEFT JOIN games g on (t.id = g.home_team_id or t.id = g.away_team_id)
LEFT JOIN teams o on o.id = if (t.id = g.home_team_id, g.away_team_id, g.home_team_id)
LEFT JOIN games og on (o.id = og.home_team_id or o.id = og.away_team_id)
WHERE t.league = 'FBS'
GROUP BY t.id

UNION ALL

SELECT ANY_VALUE(t.school) as school, ANY_VALUE(t.name) as name, ANY_VALUE(t.conference) as conference,
SUM(IF((t.id = g.home_team_id and g.home_score > g.away_score) or (t.id = g.away_team_id and g.home_score < g.away_score), 1, 0)) as win, 
SUM(IF((t.id = g.home_team_id and g.home_score < g.away_score) or (t.id = g.away_team_id and g.home_score > g.away_score), 1, 0)) as loss,
SUM(IF(t.conference = o.conference and ((t.id = g.home_team_id and g.home_score > g.away_score) or (t.id = g.away_team_id and g.home_score < g.away_score)), 1, 0)) as conf_win, 
SUM(IF(t.conference = o.conference and ((t.id = g.home_team_id and g.home_score < g.away_score) or (t.id = g.away_team_id and g.home_score > g.away_score)), 1, 0)) as conf_losses,
null as opp_wins, null as opp_losses
FROM teams t
LEFT JOIN games g on (t.id = g.home_team_id or t.id = g.away_team_id)
LEFT JOIN teams o on o.id = if (t.id = g.home_team_id, g.away_team_id, g.home_team_id)
WHERE t.league = 'FBS'
GROUP BY t.id
) q
GROUP BY school
ORDER BY wins DESC, losses ASC, opp_wins DESC, opp_losses ASC, conf_wins DESC, conf_losses ASC");

        $count = count($teams);
        for ($i=0; $i < $count-1; $i++) { 
            $swap = false;

            if ($teams[$i]->wins === $teams[$i+1]->wins
                && $teams[$i]->losses === $teams[$i+1]->losses
                && $teams[$i]->conference === $teams[$i+1]->conference
                && ($teams[$i]->conf_wins < $teams[$i+1]->conf_wins
                    || ($teams[$i]->conf_wins === $teams[$i+1]->conf_wins 
                        && $teams[$i]->conf_losses > $teams[$i+1]->conf_losses
                    )
                )
            ) {
                $swap = true;
            }




            if ($swap) {
                [$teams[$i], $teams[$i+1]] = [$teams[$i+1], $teams[$i]];
            }
        }

        return view('home')->withTeams($teams);
    }
}
