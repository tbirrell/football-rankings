<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class RankController extends Controller
{
  public function show($type = 'field', $week = 18) {
    if ($week === 18) {
      //find current week
    }
    switch ($type) {
      case 'coaches':
        return $this->coachesRanks($week);
        break;
      case 'field':
      default:
        return $this->fbsRanks($week);
        break;
    }
  }
    public function coachesRanks($week) {
      $pdo = DB::connection()->getPdo();
      $sql = "SELECT r.rank as rank, t.school as school, t.conference as conference, t.division as division, null as wins, null as losses
              FROM ranks r
              JOIN teams t on r.team_id = t.id
              JOIN rank_types rt on r.rank_type_id = rt.id
              WHERE r.week = :week
              AND (rt.name like '%coaches%' OR rt.slug like '%coaches%' OR rt.abbreviation like '%coaches%')
              ORDER BY r.rank ASC;";


      $sth = $pdo->prepare($sql);
      $sth->bindValue(':week', $week);
      $sth->execute();
      $teams = $sth->fetchAll(\PDO::FETCH_OBJ);

      $confLink = "/ranks/!conf!" . (($week != 18) ? "/week/{$week}" : '');
      return view('polls')->withTeams($teams)
                        ->withConfLink($confLink)
                        ->withWeek($week);
    }

    public function fbsRanks($week = 18)
    {
      $pdo = DB::connection()->getPdo();
      $sql = "SELECT *,
       sos * average * (1 + average) as production
       FROM (SELECT *,
                    ROUND(wins / (wins + losses), 3)             as average,
                    ROUND(opp_wins / (opp_wins + opp_losses), 3) as sos
             FROM (SELECT ANY_VALUE(id)                                  as id,
                   ANY_VALUE(the_score_id)                        as the_score_id,
                   ANY_VALUE(school)                              as school,
                   ANY_VALUE(conference)                          as conference,
                   ANY_VALUE(division)                            as division,
                   SUM(t_wins)                                    as wins,
                   SUM(t_losses)                                  as losses,
                   SUM(conf_wins)                                 as conf_wins,
                   SUM(conf_losses)                               as conf_losses,
                   SUM(o_wins)                                    as opp_wins,
                   SUM(o_losses)                                  as opp_losses,
                   SUM(oo_wins)                                   as oo_wins,
                   SUM(oo_losses)                                 as oo_losses,
                   GROUP_CONCAT(IF(t_wins = 1, o_school, null))   as beat,
                   GROUP_CONCAT(IF(t_losses = 1, o_school, null)) as lost
            FROM (SELECT ANY_VALUE(id)           as id,
                         ANY_VALUE(the_score_id) as the_score_id,
                         ANY_VALUE(school)       as school,
                         ANY_VALUE(o_school)     as o_school,
                         ANY_VALUE(conference)   as conference,
                         ANY_VALUE(division)   as division,
                         ANY_VALUE(t_wins)       as t_wins,
                         ANY_VALUE(t_losses)     as t_losses,
                         ANY_VALUE(conf_wins)    as conf_wins,
                         ANY_VALUE(conf_losses)  as conf_losses,
                         SUM(o_wins)             as o_wins,
                         SUM(o_losses)           as o_losses,
                         SUM(oo_wins)            as oo_wins,
                         SUM(oo_losses)          as oo_losses
                  FROM (SELECT ANY_VALUE(t.id)           as id,
                               ANY_VALUE(t.school)       as school,
                               ANY_VALUE(t.conference)   as conference,
                               ANY_VALUE(t.division)     as division,
                               ANY_VALUE(t.the_score_id) as the_score_id,
                               -- team info
                               ANY_VALUE(IF((t.id = g.home_team_id and g.home_score > g.away_score) or
                                            (t.id = g.away_team_id and g.home_score < g.away_score), 1,
                                            0))          as t_wins,
                               ANY_VALUE(IF((t.id = g.home_team_id and g.home_score < g.away_score) or
                                            (t.id = g.away_team_id and g.home_score > g.away_score), 1,
                                            0))          as t_losses,
                               ANY_VALUE(IF(t.conference = o.conference and
                                            ((t.id = g.home_team_id and g.home_score > g.away_score) or
                                             (t.id = g.away_team_id and g.home_score < g.away_score)), 1,
                                            0))          as conf_wins,
                               ANY_VALUE(IF(t.conference = o.conference and
                                            ((t.id = g.home_team_id and g.home_score < g.away_score) or
                                             (t.id = g.away_team_id and g.home_score > g.away_score)), 1,
                                            0))          as conf_losses,
                               -- opponents info
                               o.id                      as o_id,
                               ANY_VALUE(o.school)       as o_school,
                               ANY_VALUE(IF((o.id = og.home_team_id and og.home_score > og.away_score) or
                                            (o.id = og.away_team_id and og.home_score < og.away_score), 1,
                                            0))          as o_wins,
                               ANY_VALUE(IF((o.id = og.home_team_id and og.home_score < og.away_score) or
                                            (o.id = og.away_team_id and og.home_score > og.away_score), 1,
                                            0))          as o_losses,
                               -- opponents of opponents info
                               ANY_VALUE(oo.id)          as oo_id,
                               SUM(IF((oo.id = oog.home_team_id and oog.home_score > og.away_score) or
                                      (oo.id = oog.away_team_id and oog.home_score < oog.away_score), 1,
                                      0))                as oo_wins,
                               SUM(IF((oo.id = oog.home_team_id and oog.home_score < og.away_score) or
                                      (oo.id = oog.away_team_id and oog.home_score > oog.away_score), 1,
                                      0))                as oo_losses
                               -- team info
                        FROM teams t
                              LEFT JOIN games g on (t.id = g.home_team_id or t.id = g.away_team_id)
                         -- opponents info
                              LEFT JOIN teams o on o.id = if(t.id = g.home_team_id, g.away_team_id, g.home_team_id)
                              LEFT JOIN games og on (o.id = og.home_team_id or o.id = og.away_team_id)
                         and t.id <> if(o.id = og.home_team_id, og.away_team_id, og.home_team_id)
                         -- opponents of opponents info
                              LEFT JOIN teams oo on oo.id = if(o.id = og.home_team_id, og.away_team_id, og.home_team_id)
                              LEFT JOIN games oog on (oo.id = oog.home_team_id or oo.id = oog.away_team_id)
                         and o.id <> if(oo.id = oog.home_team_id, oog.away_team_id, oog.home_team_id)
                        WHERE t.league = 'FBS'
                        GROUP BY t.id, o.id, oo.id) opp_opp_record -- group by opponents of opponents to derive their records
                  group by id, o_id) opp_record -- group by oppoents to to derive their records and sum up opponents of opponents records
            group by id) team_record -- group by teams to to derive their records and sum up opponents records
     ) query -- handle strength of schedule and batting average math and pass the rest of the info through
order by production desc -- sort by ranking metric";


      $sth = $pdo->prepare($sql);
      $sth->bindValue(':week', $week);
      $sth->execute();
      $teams = $sth->fetchAll(\PDO::FETCH_OBJ);

      $count = count($teams);


      //conf priority
      for ($i=0; $i < $count-2; $i++) {
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
      //head-to-head
      do {
        $changed = false;
        for ($i=0; $i < $count-2; $i++) {
            // dump("Checking {$teams[$i]->school} and ".$teams[$i+1]->school);
            if (strpos($teams[$i]->lost, $teams[$i+1]->school.',') !== false || $teams[$i]->lost === $teams[$i+1]->school) {

                // dump("{$teams[$i]->school} lost to ".$teams[$i+1]->school);
                [$teams[$i], $teams[$i+1]] = [$teams[$i+1], $teams[$i]];

                $changed = true;
            }
        }
      } while ($changed);

      $confLink = "/ranks/!conf!" . (($week != 18) ? "/week/{$week}" : '');
      return view('fbs')->withTeams($teams)
                        ->withConfLink($confLink)
                        ->withWeek($week);
    }


    public function fbsRanksBcs($week = 18)
    {
      $pdo = DB::connection()->getPdo();
      $sql = "SELECT *,
       sos * average * (1 + average) as production
       FROM (SELECT *,
                    ROUND(wins / (wins + losses), 3) as average,
                    ROUND((2 * (ROUND(opp_wins / (opp_wins + opp_losses), 3)) * (ROUND(oo_wins / (oo_wins + oo_losses), 3))) / 3, 3) as sos
             FROM (SELECT ANY_VALUE(id)                                  as id,
                   ANY_VALUE(the_score_id)                        as the_score_id,
                   ANY_VALUE(school)                              as school,
                   ANY_VALUE(conference)                          as conference,
                   ANY_VALUE(division)                          as division,
                   SUM(t_wins)                                    as wins,
                   SUM(t_losses)                                  as losses,
                   SUM(conf_wins)                                 as conf_wins,
                   SUM(conf_losses)                               as conf_losses,
                   SUM(o_wins)                                    as opp_wins,
                   SUM(o_losses)                                  as opp_losses,
                   SUM(oo_wins)                                   as oo_wins,
                   SUM(oo_losses)                                 as oo_losses,
                   GROUP_CONCAT(IF(t_wins = 1, o_school, null))   as beat,
                   GROUP_CONCAT(IF(t_losses = 1, o_school, null)) as lost
            FROM (SELECT ANY_VALUE(id)           as id,
                         ANY_VALUE(the_score_id) as the_score_id,
                         ANY_VALUE(school)       as school,
                         ANY_VALUE(o_school)     as o_school,
                         ANY_VALUE(conference)   as conference,
                         ANY_VALUE(division)   as division,
                         ANY_VALUE(t_wins)       as t_wins,
                         ANY_VALUE(t_losses)     as t_losses,
                         ANY_VALUE(conf_wins)    as conf_wins,
                         ANY_VALUE(conf_losses)  as conf_losses,
                         SUM(o_wins)             as o_wins,
                         SUM(o_losses)           as o_losses,
                         SUM(oo_wins)            as oo_wins,
                         SUM(oo_losses)          as oo_losses
                  FROM (SELECT ANY_VALUE(t.id)           as id,
                               ANY_VALUE(t.school)       as school,
                               ANY_VALUE(t.conference)   as conference,
                               ANY_VALUE(t.division)     as division,
                               ANY_VALUE(t.the_score_id) as the_score_id,
                               -- team info
                               ANY_VALUE(IF((t.id = g.home_team_id and g.home_score > g.away_score) or
                                            (t.id = g.away_team_id and g.home_score < g.away_score), 1,
                                            0))          as t_wins,
                               ANY_VALUE(IF((t.id = g.home_team_id and g.home_score < g.away_score) or
                                            (t.id = g.away_team_id and g.home_score > g.away_score), 1,
                                            0))          as t_losses,
                               ANY_VALUE(IF(t.conference = o.conference and
                                            ((t.id = g.home_team_id and g.home_score > g.away_score) or
                                             (t.id = g.away_team_id and g.home_score < g.away_score)), 1,
                                            0))          as conf_wins,
                               ANY_VALUE(IF(t.conference = o.conference and
                                            ((t.id = g.home_team_id and g.home_score < g.away_score) or
                                             (t.id = g.away_team_id and g.home_score > g.away_score)), 1,
                                            0))          as conf_losses,
                               -- opponents info
                               o.id                      as o_id,
                               ANY_VALUE(o.school)       as o_school,
                               ANY_VALUE(IF((o.id = og.home_team_id and og.home_score > og.away_score) or
                                            (o.id = og.away_team_id and og.home_score < og.away_score), 1,
                                            0))          as o_wins,
                               ANY_VALUE(IF((o.id = og.home_team_id and og.home_score < og.away_score) or
                                            (o.id = og.away_team_id and og.home_score > og.away_score), 1,
                                            0))          as o_losses,
                               -- opponents of opponents info
                               ANY_VALUE(oo.id)          as oo_id,
                               SUM(IF((oo.id = oog.home_team_id and oog.home_score > og.away_score) or
                                      (oo.id = oog.away_team_id and oog.home_score < oog.away_score), 1,
                                      0))                as oo_wins,
                               SUM(IF((oo.id = oog.home_team_id and oog.home_score < og.away_score) or
                                      (oo.id = oog.away_team_id and oog.home_score > oog.away_score), 1,
                                      0))                as oo_losses
                               -- team info
                        FROM teams t
                              LEFT JOIN games g on (t.id = g.home_team_id or t.id = g.away_team_id)
                         -- opponents info
                              LEFT JOIN teams o on o.id = if(t.id = g.home_team_id, g.away_team_id, g.home_team_id)
                              LEFT JOIN games og on (o.id = og.home_team_id or o.id = og.away_team_id)
                         and t.id <> if(o.id = og.home_team_id, og.away_team_id, og.home_team_id)
                         -- opponents of opponents info
                              LEFT JOIN teams oo on oo.id = if(o.id = og.home_team_id, og.away_team_id, og.home_team_id)
                              LEFT JOIN games oog on (oo.id = oog.home_team_id or oo.id = oog.away_team_id)
                         and o.id <> if(oo.id = oog.home_team_id, oog.away_team_id, oog.home_team_id)
                        WHERE t.league = 'FBS'
                        GROUP BY t.id, o.id, oo.id) opp_opp_record -- group by opponents of opponents to derive their records
                  group by id, o_id) opp_record -- group by oppoents to to derive their records and sum up opponents of opponents records
            group by id) team_record -- group by teams to to derive their records and sum up opponents records
     ) query -- handle strength of schedule and batting average math and pass the rest of the info through
order by production desc -- sort by ranking metric";


      $sth = $pdo->prepare($sql);
//      $sth->bindValue(':week', $week);
      $sth->execute();
      $teams = $sth->fetchAll(\PDO::FETCH_OBJ);

      $count = count($teams);

      //conf priority
      for ($i=0; $i < $count-2; $i++) {
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
      //head-to-head
      do {
        $changed = false;
        for ($i=0; $i < $count-2; $i++) {
            // dump("Checking {$teams[$i]->school} and ".$teams[$i+1]->school);
            if (strpos($teams[$i]->lost, $teams[$i+1]->school.',') !== false || $teams[$i]->lost === $teams[$i+1]->school) {

                // dump("{$teams[$i]->school} lost to ".$teams[$i+1]->school);
                [$teams[$i], $teams[$i+1]] = [$teams[$i+1], $teams[$i]];

                $changed = true;
            }
        }
      } while ($changed);

      $confLink = "/ranks/!conf!" . (($week != 18) ? "/week/{$week}" : '');
      return view('fbs')->withTeams($teams)
                        ->withConfLink($confLink)
                        ->withWeek($week);
    }


    public function fbsRanksOld($week = 18)
    {
      $pdo = DB::connection()->getPdo();
      $sql = "SELECT ANY_VALUE(school) as school, COALESCE(GROUP_CONCAT(the_score_id)) as the_score_id, COALESCE(GROUP_CONCAT(conference)) as conference, COALESCE(GROUP_CONCAT(division)) as division, SUM(wins) as wins, SUM(losses) as losses, SUM(opp_wins) as opp_wins, SUM(opp_losses) as opp_losses, SUM(conf_wins) as conf_wins, SUM(conf_losses) as conf_losses, COALESCE(GROUP_CONCAT(beat)) as beat, COALESCE(GROUP_CONCAT(lost)) as lost, CONCAT(SUM(wins),'-',SUM(losses),'-',SUM(opp_wins),'-',SUM(opp_losses),'-',SUM(conf_wins),'-',SUM(conf_losses)) as tuple

                FROM (
                    SELECT ANY_VALUE(t.school) as school, null as the_score_id, null as conference, null as division, null as wins, null as losses, null as conf_wins, null as conf_losses,
                    SUM(IF((o.id = og.home_team_id and og.home_score > og.away_score) or (o.id = og.away_team_id and og.home_score < og.away_score), 1, 0)) as opp_wins,
                    SUM(IF((o.id = og.home_team_id and og.home_score < og.away_score) or (o.id = og.away_team_id and og.home_score > og.away_score), 1, 0)) as opp_losses,
                    null as beat, null as lost
                    FROM teams t
                    LEFT JOIN games g on (t.id = g.home_team_id or t.id = g.away_team_id)
                    LEFT JOIN teams o on o.id = if (t.id = g.home_team_id, g.away_team_id, g.home_team_id)
                    LEFT JOIN games og on (o.id = og.home_team_id or o.id = og.away_team_id)
                    WHERE t.league = 'FBS'
                    AND g.week <= :week
                    AND og.week <= :week
                    GROUP BY t.id

                    UNION ALL

                    SELECT ANY_VALUE(t.school) as school, ANY_VALUE(t.the_score_id) as the_score_id, ANY_VALUE(t.conference) as conference, ANY_VALUE(t.division) as division,
                    SUM(IF((t.id = g.home_team_id and g.home_score > g.away_score) or (t.id = g.away_team_id and g.home_score < g.away_score), 1, 0)) as win,
                    SUM(IF((t.id = g.home_team_id and g.home_score < g.away_score) or (t.id = g.away_team_id and g.home_score > g.away_score), 1, 0)) as loss,
                    SUM(IF(t.conference = o.conference and ((t.id = g.home_team_id and g.home_score > g.away_score) or (t.id = g.away_team_id and g.home_score < g.away_score)), 1, 0)) as conf_win,
                    SUM(IF(t.conference = o.conference and ((t.id = g.home_team_id and g.home_score < g.away_score) or (t.id = g.away_team_id and g.home_score > g.away_score)), 1, 0)) as conf_losses,
                    null as opp_wins, null as opp_losses,
                    GROUP_CONCAT(IF((t.id = g.home_team_id and g.home_score > g.away_score) or (t.id = g.away_team_id and g.home_score < g.away_score), o.school, null)) as beat,
                    GROUP_CONCAT(IF((t.id = g.home_team_id and g.home_score < g.away_score) or (t.id = g.away_team_id and g.home_score > g.away_score), o.school, null)) as lost
                    FROM teams t
                    LEFT JOIN games g on (t.id = g.home_team_id or t.id = g.away_team_id)
                    LEFT JOIN teams o on o.id = if (t.id = g.home_team_id, g.away_team_id, g.home_team_id)
                    WHERE t.league = 'FBS'
                    AND g.week <= :week
                    GROUP BY t.id
                ) q
                GROUP BY school
                ORDER BY wins DESC, losses ASC, opp_wins DESC, opp_losses ASC, conf_wins DESC, conf_losses ASC";


      $sth = $pdo->prepare($sql);
      $sth->bindValue(':week', $week);
      $sth->execute();
      $teams = $sth->fetchAll(\PDO::FETCH_OBJ);

      $count = count($teams);

      //SoS demerits
      // $chunks = [];
      // foreach ($teams as $team) {
      //   $chunks["{$team->wins}wins"][] = $team;
      // }
      // $teams = [];
      // $cc = count($chunks);
      // for ($i=$cc-1; $i >= 0; $i--) {

      //   if (count($chunks["{$i}wins"]) > 1 && $i > 7) {
      //     $lowest_sos = null;
      //     $k = null;
      //     foreach ($chunks["{$i}wins"] as $key => $value) {
      //       if ($lowest_sos !== null) {
      //         // dump("Checking {$value->school}({$value->opp_wins}) against {$lowest_sos->school}({$lowest_sos->opp_wins})");
      //       }
      //       if ($lowest_sos === null || $lowest_sos->opp_wins > $value->opp_wins) {
      //         if ($lowest_sos !== null) {
      //           // dump("{$value->school} replacing {$lowest_sos->school}");
      //         } else {
      //           // dump("{$value->school} getting set with key {$key}");
      //         }
      //         $lowest_sos = $value;
      //         $k = $key;
      //       }
      //     }
      //     // dump("Moving ".$chunks["{$i}wins"][$k]->school);
      //     unset($chunks["{$i}wins"][$k]);

      //     array_unshift($chunks[($i-1)."wins"], $lowest_sos);

      //     usort($chunks["{$i}wins"], function($a, $b){
      //       return strcmp($a->tuple, $b->tuple);
      //     });
      //   }

      //   // dump('----');
      //   $teams = array_merge($teams, $chunks["{$i}wins"]);
      // }
// die;
      //conf priority
      for ($i=0; $i < $count-2; $i++) {
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
      //head-to-head
      do {
        $changed = false;
        for ($i=0; $i < $count-2; $i++) {
            // dump("Checking {$teams[$i]->school} and ".$teams[$i+1]->school);
            if (strpos($teams[$i]->lost, $teams[$i+1]->school.',') !== false || $teams[$i]->lost === $teams[$i+1]->school) {

                // dump("{$teams[$i]->school} lost to ".$teams[$i+1]->school);
                [$teams[$i], $teams[$i+1]] = [$teams[$i+1], $teams[$i]];

                $changed = true;
            }
        }
      } while ($changed);

      $confLink = "/ranks/!conf!" . (($week != 18) ? "/week/{$week}" : '');
      return view('fbs')->withTeams($teams)
                        ->withConfLink($confLink)
                        ->withWeek($week);
    }

    public function conferenceRanks($conference, $week = 18)
    {
      $conference = ($conference === 'pac-12') ?: str_replace('-', ' ', $conference);
      $pdo = DB::connection()->getPdo();
      $sql = "SELECT ANY_VALUE(school) as school, COALESCE(GROUP_CONCAT(the_score_id)) as the_score_id, COALESCE(GROUP_CONCAT(conference)) as conference, COALESCE(GROUP_CONCAT(division)) as division, SUM(wins) as wins, SUM(losses) as losses, SUM(opp_wins) as opp_wins, SUM(opp_losses) as opp_losses, SUM(conf_wins) as conf_wins, SUM(conf_losses) as conf_losses, COALESCE(GROUP_CONCAT(beat)) as beat, COALESCE(GROUP_CONCAT(lost)) as lost
              FROM
                (SELECT ANY_VALUE(t.school) as school, null as the_score_id, null as conference, null as division, null as wins, null as losses, null as conf_wins, null as conf_losses,
                SUM(IF((o.id = og.home_team_id and og.home_score > og.away_score) or (o.id = og.away_team_id and og.home_score < og.away_score), 1, 0)) as opp_wins,
                SUM(IF((o.id = og.home_team_id and og.home_score < og.away_score) or (o.id = og.away_team_id and og.home_score > og.away_score), 1, 0)) as opp_losses,
                null as beat, null as lost
                FROM teams t
                LEFT JOIN games g on (t.id = g.home_team_id or t.id = g.away_team_id)
                LEFT JOIN teams o on o.id = if (t.id = g.home_team_id, g.away_team_id, g.home_team_id)
                LEFT JOIN games og on (o.id = og.home_team_id or o.id = og.away_team_id)
                WHERE t.league = 'FBS'
                AND t.conference = :conference
                AND g.week <= :week
                AND og.week <= :week
                GROUP BY t.id

                UNION ALL

                SELECT ANY_VALUE(t.school) as school, ANY_VALUE(t.the_score_id) as the_score_id, ANY_VALUE(t.conference) as conference, ANY_VALUE(t.division) as division,
                SUM(IF((t.id = g.home_team_id and g.home_score > g.away_score) or (t.id = g.away_team_id and g.home_score < g.away_score), 1, 0)) as win,
                SUM(IF((t.id = g.home_team_id and g.home_score < g.away_score) or (t.id = g.away_team_id and g.home_score > g.away_score), 1, 0)) as loss,
                SUM(IF(t.conference = o.conference and ((t.id = g.home_team_id and g.home_score > g.away_score) or (t.id = g.away_team_id and g.home_score < g.away_score)), 1, 0)) as conf_win,
                SUM(IF(t.conference = o.conference and ((t.id = g.home_team_id and g.home_score < g.away_score) or (t.id = g.away_team_id and g.home_score > g.away_score)), 1, 0)) as conf_losses,
                null as opp_wins, null as opp_losses,
                GROUP_CONCAT(IF((t.id = g.home_team_id and g.home_score > g.away_score) or (t.id = g.away_team_id and g.home_score < g.away_score), o.school, null)) as beat,
                GROUP_CONCAT(IF((t.id = g.home_team_id and g.home_score < g.away_score) or (t.id = g.away_team_id and g.home_score > g.away_score), o.school, null)) as lost
                FROM teams t
                LEFT JOIN games g on (t.id = g.home_team_id or t.id = g.away_team_id)
                LEFT JOIN teams o on o.id = if (t.id = g.home_team_id, g.away_team_id, g.home_team_id)
                WHERE t.league = 'FBS'
                AND t.conference = :conference
                AND g.week <= :week
                GROUP BY t.id
                ) q
              GROUP BY school
              ORDER BY conf_wins DESC, conf_losses ASC, wins DESC, losses ASC, opp_wins DESC, opp_losses ASC";


      $sth = $pdo->prepare($sql);
      $sth->bindValue(':conference', $conference);
      $sth->bindValue(':week', $week);
      $sth->execute();
      $teams = $sth->fetchAll(\PDO::FETCH_OBJ);

      //head-to-head

      $count = count($teams);
      $loop = 0;
      do {
        $changed = false;
        for ($i=0; $i < $count-1; $i++) {
            if (strpos($teams[$i]->lost, $teams[$i+1]->school) !== false) {
                [$teams[$i], $teams[$i+1]] = [$teams[$i+1], $teams[$i]];
                $changed = true;
            }
        }
        $loop++;
      } while ($changed && $loop < 10);

      return view('conference')->withTeams($teams);
    }

    public function fetchCfpSite(){
      $ranks = json_decode($this->load('https://collegefootballplayoff.com/services/rankings.ashx'));

        // dump($ranks);
      $latest = [];
      foreach ($ranks->weeks as $week) {
        if ($latest === []) {
          $latest = $week;
          continue;
        }
        if ($latest->release_date < $week->release_date) {
          $latest = $week;
        }
      }
      dump($latest);
    }
    public function fetchAp(){}
    public function fetchCoaches(){
      $pdo = DB::connection()->getPdo();

      //have ranks?
      $sth = $pdo->query("SELECT id FROM rank_types WHERE name = 'Coaches Poll'");
      $rank_type = $sth->fetchAll(\PDO::FETCH_ASSOC);

      $sth = $pdo->prepare('SELECT count(1) AS count FROM ranks WHERE week = :week AND rank_type_id = :rank_type_id');
      $sth->bindValue(':week', 10);
      $sth->bindValue(':rank_type_id', $rank_type[0]['id']);
      $sth->execute();
      $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

      if ($result[0]['count'] >= 25) {
        return;
      }

      $sth = $pdo->query("SELECT id, school FROM teams");
      $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
      $teams = [];
      foreach ($result as $team) {
        $teams[$team['school']] = $team['id'];
      }

      $sth = $pdo->query("SELECT id FROM rank_types WHERE name = 'Coaches Poll'");
      $rank_type = $sth->fetchAll(\PDO::FETCH_ASSOC);

      $query = "INSERT INTO ranks (week, team_id, rank_type_id, rank) VALUES";

      //https://simplehtmldom.sourceforge.io/manual.htm
      $dom = \voku\helper\HtmlDomParser::file_get_html('https://www.thescore.com/ncaaf/standings/Coaches');
      $elements = $dom->findMulti('.TableGroupStandings__teamRanking--1g80_');

      $ranks = [];
      foreach ($elements as $e) {
        preg_match('/(\d+)(.*)/', $e->plaintext, $m);
        $ranks[] = ['rank' => $m[1], 'team' => $m[2]];
        $query .= "(10,{$teams[$m[2]]},{$rank_type[0]['id']},{$m[1]}),";
      }

      $query = rtrim($query, ',');
      // dump($query);
      $sth = $pdo->query($query);
    }

  protected function load($url,$options=array()) {
    $default_options = array(
        'method'        => 'get',
        'post_data'        => false,
        'return_info'    => false,
        'return_body'    => true,
        'cache'            => false,
        'referer'        => '',
        'headers'        => array(),
        'session'        => false,
        'session_close'    => false,
    );
    // Sets the default options.
    foreach($default_options as $opt=>$value) {
        if(!isset($options[$opt])) $options[$opt] = $value;
    }

    $url_parts = parse_url($url);
    $ch = false;
    $info = array(//Currently only supported by curl.
        'http_code'    => 200
    );
    $response = '';

    $send_header = array(
        'Accept' => 'text/*',
        'User-Agent' => 'BinGet/1.00.A (http://www.bin-co.com/php/scripts/load/)'
    ) + $options['headers']; // Add custom headers provided by the user.

    if($options['cache']) {
        $cache_folder = joinPath(sys_get_temp_dir(), 'php-load-function');
        if(isset($options['cache_folder'])) $cache_folder = $options['cache_folder'];
        if(!file_exists($cache_folder)) {
            $old_umask = umask(0); // Or the folder will not get write permission for everybody.
            mkdir($cache_folder, 0777);
            umask($old_umask);
        }

        $cache_file_name = md5($url) . '.cache';
        $cache_file = joinPath($cache_folder, $cache_file_name); //Don't change the variable name - used at the end of the function.

        if(file_exists($cache_file)) { // Cached file exists - return that.
            $response = file_get_contents($cache_file);

            //Seperate header and content
            $separator_position = strpos($response,"\r\n\r\n");
            $header_text = substr($response,0,$separator_position);
            $body = substr($response,$separator_position+4);

            foreach(explode("\n",$header_text) as $line) {
                $parts = explode(": ",$line);
                if(count($parts) == 2) $headers[$parts[0]] = chop($parts[1]);
            }
            $headers['cached'] = true;

            if(!$options['return_info']) return $body;
            else return array('headers' => $headers, 'body' => $body, 'info' => array('cached'=>true));
        }
    }

    if(isset($options['post_data'])) { //There is an option to specify some data to be posted.
        $options['method'] = 'post';

        if(is_array($options['post_data'])) { //The data is in array format.
            $post_data = array();
            foreach($options['post_data'] as $key=>$value) {
                $post_data[] = "$key=" . urlencode($value);
            }
            $url_parts['query'] = implode('&', $post_data);
        } else { //Its a string
            $url_parts['query'] = $options['post_data'];
        }
    } elseif(isset($options['multipart_data'])) { //There is an option to specify some data to be posted.
        $options['method'] = 'post';
        $url_parts['query'] = $options['multipart_data'];
        /*
            This array consists of a name-indexed set of options.
            For example,
            'name' => array('option' => value)
            Available options are:
            filename: the name to report when uploading a file.
            type: the mime type of the file being uploaded (not used with curl).
            binary: a flag to tell the other end that the file is being uploaded in binary mode (not used with curl).
            contents: the file contents. More efficient for fsockopen if you already have the file contents.
            fromfile: the file to upload. More efficient for curl if you don't have the file contents.

            Note the name of the file specified with fromfile overrides filename when using curl.
         */
    }

    ///////////////////////////// Curl /////////////////////////////////////
    //If curl is available, use curl to get the data.
    if(function_exists("curl_init")
                and (!(isset($options['use']) and $options['use'] == 'fsocketopen'))) { //Don't use curl if it is specifically stated to use fsocketopen in the options

        if(isset($options['post_data'])) { //There is an option to specify some data to be posted.
            $page = $url;
            $options['method'] = 'post';

            if(is_array($options['post_data'])) { //The data is in array format.
                $post_data = array();
                foreach($options['post_data'] as $key=>$value) {
                    $post_data[] = "$key=" . urlencode($value);
                }
                $url_parts['query'] = implode('&', $post_data);

            } else { //Its a string
                $url_parts['query'] = $options['post_data'];
            }
        } else {
            if(isset($options['method']) and $options['method'] == 'post') {
                $page = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
            } else {
                $page = $url;
            }
        }

        if($options['session'] and isset($GLOBALS['_binget_curl_session'])) $ch = $GLOBALS['_binget_curl_session']; //Session is stored in a global variable
        else $ch = curl_init($url_parts['host']);

        curl_setopt($ch, CURLOPT_URL, $page) or die("Invalid cURL Handle Resouce");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Just return the data - not print the whole thing.
        curl_setopt($ch, CURLOPT_HEADER, true); //We need the headers
        curl_setopt($ch, CURLOPT_NOBODY, !($options['return_body'])); //The content - if true, will not download the contents. There is a ! operation - don't remove it.
        $tmpdir = NULL; //This acts as a flag for us to clean up temp files
        if(isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
            curl_setopt($ch, CURLOPT_POST, true);
            if(is_array($url_parts['query'])) {
                //multipart form data (eg. file upload)
                $postdata = array();
                foreach ($url_parts['query'] as $name => $data) {
                    if (isset($data['contents']) && isset($data['filename'])) {
                        if (!isset($tmpdir)) { //If the temporary folder is not specifed - and we want to upload a file, create a temp folder.
                            //  :TODO:
                            $dir = sys_get_temp_dir();
                            $prefix = 'load';

                            if (substr($dir, -1) != '/') $dir .= '/';
                            do {
                                $path = $dir . $prefix . mt_rand(0, 9999999);
                            } while (!mkdir($path, $mode));

                            $tmpdir = $path;
                        }
                        $tmpfile = $tmpdir.'/'.$data['filename'];
                        file_put_contents($tmpfile, $data['contents']);
                        $data['fromfile'] = $tmpfile;
                    }
                    if (isset($data['fromfile'])) {
                        // Not sure how to pass mime type and/or the 'use binary' flag
                        $postdata[$name] = '@'.$data['fromfile'];
                    } elseif (isset($data['contents'])) {
                        $postdata[$name] = $data['contents'];
                    } else {
                        $postdata[$name] = '';
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $url_parts['query']);
            }
        }

        //Set the headers our spiders sends
        curl_setopt($ch, CURLOPT_USERAGENT, $send_header['User-Agent']); //The Name of the UserAgent we will be using ;)
        $custom_headers = array("Accept: " . $send_header['Accept'] );
        if(isset($options['modified_since']))
            array_push($custom_headers,"If-Modified-Since: ".gmdate('D, d M Y H:i:s \G\M\T',strtotime($options['modified_since'])));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
        if($options['referer']) curl_setopt($ch, CURLOPT_REFERER, $options['referer']);

        curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/binget-cookie.txt"); //If ever needed...
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $custom_headers = array();
        unset($send_header['User-Agent']); // Already done (above)
        foreach ($send_header as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $custom_headers[] = "$name: $item";
                }
            } else {
                $custom_headers[] = "$name: $value";
            }
        }
        if(isset($url_parts['user']) and isset($url_parts['pass'])) {
            $custom_headers[] = "Authorization: Basic ".base64_encode($url_parts['user'].':'.$url_parts['pass']);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);

        $response = curl_exec($ch);

        if(isset($tmpdir)) {
            //rmdirr($tmpdir); //Cleanup any temporary files :TODO:
        }

        $info = curl_getinfo($ch); //Some information on the fetch

        if($options['session'] and !$options['session_close']) $GLOBALS['_binget_curl_session'] = $ch; //Dont close the curl session. We may need it later - save it to a global variable
        else curl_close($ch);  //If the session option is not set, close the session.

    //////////////////////////////////////////// FSockOpen //////////////////////////////
    } else { //If there is no curl, use fsocketopen - but keep in mind that most advanced features will be lost with this approch.

        if(!isset($url_parts['query']) || (isset($options['method']) and $options['method'] == 'post'))
            $page = $url_parts['path'];
        else
            $page = $url_parts['path'] . '?' . $url_parts['query'];

        if(!isset($url_parts['port'])) $url_parts['port'] = ($url_parts['scheme'] == 'https' ? 443 : 80);
        $host = ($url_parts['scheme'] == 'https' ? 'ssl://' : '').$url_parts['host'];
        $fp = fsockopen($host, $url_parts['port'], $errno, $errstr, 30);
        if ($fp) {
            $out = '';
            if(isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
                $out .= "POST $page HTTP/1.1\r\n";
            } else {
                $out .= "GET $page HTTP/1.0\r\n"; //HTTP/1.0 is much easier to handle than HTTP/1.1
            }
            $out .= "Host: $url_parts[host]\r\n";
        foreach ($send_header as $name => $value) {
        if (is_array($value)) {
            foreach ($value as $item) {
            $out .= "$name: $item\r\n";
            }
        } else {
            $out .= "$name: $value\r\n";
        }
        }
            $out .= "Connection: Close\r\n";

            //HTTP Basic Authorization support
            if(isset($url_parts['user']) and isset($url_parts['pass'])) {
                $out .= "Authorization: Basic ".base64_encode($url_parts['user'].':'.$url_parts['pass']) . "\r\n";
            }

            //If the request is post - pass the data in a special way.
            if(isset($options['method']) and $options['method'] == 'post') {
                if(is_array($url_parts['query'])) {
                    //multipart form data (eg. file upload)

                    // Make a random (hopefully unique) identifier for the boundary
                    srand((double)microtime()*1000000);
                    $boundary = "---------------------------".substr(md5(rand(0,32000)),0,10);

                    $postdata = array();
                    $postdata[] = '--'.$boundary;
                    foreach ($url_parts['query'] as $name => $data) {
                        $disposition = 'Content-Disposition: form-data; name="'.$name.'"';
                        if (isset($data['filename'])) {
                            $disposition .= '; filename="'.$data['filename'].'"';
                        }
                        $postdata[] = $disposition;
                        if (isset($data['type'])) {
                            $postdata[] = 'Content-Type: '.$data['type'];
                        }
                        if (isset($data['binary']) && $data['binary']) {
                            $postdata[] = 'Content-Transfer-Encoding: binary';
                        } else {
                            $postdata[] = '';
                        }
                        if (isset($data['fromfile'])) {
                            $data['contents'] = file_get_contents($data['fromfile']);
                        }
                        if (isset($data['contents'])) {
                            $postdata[] = $data['contents'];
                        } else {
                            $postdata[] = '';
                        }
                        $postdata[] = '--'.$boundary;
                    }
                    $postdata = implode("\r\n", $postdata)."\r\n";
                    $length = strlen($postdata);
                    $postdata = 'Content-Type: multipart/form-data; boundary='.$boundary."\r\n".
                                'Content-Length: '.$length."\r\n".
                                "\r\n".
                                $postdata;

                    $out .= $postdata;
                } else {
                    $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
                    $out .= 'Content-Length: ' . strlen($url_parts['query']) . "\r\n";
                    $out .= "\r\n" . $url_parts['query'];
                }
            }
            $out .= "\r\n";

            fwrite($fp, $out);
            while (!feof($fp)) {
                $response .= fgets($fp, 128);
            }
            fclose($fp);
        }
    }

    //Get the headers in an associative array
    $headers = array();

    if($info['http_code'] == 404) {
        $body = "";
        $headers['Status'] = 404;
    } else {
        //Seperate header and content
        $header_text = substr($response, 0, $info['header_size']);
        $body = substr($response, $info['header_size']);

        foreach(explode("\n",$header_text) as $line) {
            $parts = explode(": ",$line);
            if(count($parts) == 2) {
                if (isset($headers[$parts[0]])) {
                    if (is_array($headers[$parts[0]])) $headers[$parts[0]][] = chop($parts[1]);
                    else $headers[$parts[0]] = array($headers[$parts[0]], chop($parts[1]));
                } else {
                    $headers[$parts[0]] = chop($parts[1]);
                }
            }
        }

    }

    if(isset($cache_file)) { //Should we cache the URL?
        file_put_contents($cache_file, $response);
    }

    if($options['return_info']) return array('headers' => $headers, 'body' => $body, 'info' => $info, 'curl_handle'=>$ch);
    return $body;
  }
}
