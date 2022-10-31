@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>All FBS @if($week != 18)
                - Week {{$week}}
            @endif</h1>
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
                    @if($teams[0]->oo_wins ?? false)
                        <th>Opp Of Opp Record</th>
                    @endif
                    @if($teams[0]->production ?? false)
                        <th>Production</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @foreach($teams as $i => $team)
                    <tr>
                        <td>{{$i+1}}</td>
                        <td>
                            <img src="https://d1si3tbndbzwz9.cloudfront.net/football/team/{{$team->the_score_id}}/small_logo.png" style="width: 24px; height: 24px;"> {{$team->school}}
                        </td>
                        <td>
                            <a href="{{str_replace('!conf!', strtolower(str_replace(' ','-',$team->conference)), $confLink)}}">{{$team->conference}} {{$team->division}}</a>
                        </td>
                        <td>{{$team->wins}}-{{$team->losses}}</td>
                        <td>{{$team->opp_wins}}-{{$team->opp_losses}}</td>
                        <td>{{$team->conf_wins}}-{{$team->conf_losses}}</td>
                        @if($team->oo_wins ?? false)
                            <td>{{$team->oo_wins}}-{{$team->oo_losses}}</td>
                        @endif
                        @if($team->production ?? false)
                        <td>{{number_format($team->production, 3)}}</td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
