@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <table class="table table-striped table-condensed">
          <thead>
            <tr>
              <th>Rank</th>
              <th>School</th>
              <th>Conf Record</th>
              <th>Record</th>
              <th>Opp Record</th>
            </tr>
          </thead>
          <tbody>
            @foreach($teams as $i => $team)
              <tr>
                <td>{{$i+1}}</td>
                <td><img src="https://d1si3tbndbzwz9.cloudfront.net/football/team/{{$team->the_score_id}}/small_logo.png" style="width: 24px; height: 24px;"> {{$team->school}}</td>
                <td>{{$team->conf_wins}}-{{$team->conf_losses}}</td>
                <td>{{$team->wins}}-{{$team->losses}}</td>
                <td>{{$team->opp_wins}}-{{$team->opp_losses}}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
    </div>
</div>
@endsection
