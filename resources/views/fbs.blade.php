@extends('layouts.app')

@section('content')
<div class="container">
  <h1>All FBS @if($week != 18)- Week {{$week}}@endif</h1>
    <div class="row">
        <table class="table table-striped table-condensed">
          <thead>
            <tr>
              <th>Rank</th>
              <th>School</th>
              <th>Conference</th>
              <th>Record</th>
              <th>Opp Record</th>
              <th>Conf Record</th>
              {{-- <th>A</th> --}}
            </tr>
          </thead>
          <tbody>
            @foreach($teams as $i => $team)
              <tr>
                <td>{{$i+1}}</td>
                <td><img src="https://d1si3tbndbzwz9.cloudfront.net/football/team/{{$team->the_score_id}}/small_logo.png" style="width: 24px; height: 24px;"> {{$team->school}}</td>
                <td><a href="{{str_replace('!conf!', strtolower(str_replace(' ','-',$team->conference)), $confLink)}}">{{$team->conference}} {{$team->division}}</a></td>
                <td>{{$team->wins}}-{{$team->losses}}</td>
                <td>{{$team->opp_wins}}-{{$team->opp_losses}}</td>
                <td>{{$team->conf_wins}}-{{$team->conf_losses}}</td>
                <td>{{$team->opp_wins}}</td>
                {{-- <td>{{round(($team->opp_wins/($team->opp_wins+$team->opp_losses))/($team->wins/($team->wins+$team->losses)), 2)}}%</td> --}}
              </tr>
            @endforeach
          </tbody>
        </table>
    </div>
</div>
@endsection
