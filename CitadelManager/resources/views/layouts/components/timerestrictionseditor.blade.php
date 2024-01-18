<div class="grid">
    <h2>
        <small>Time Restrictions</small>
    </h2>
    <hr class="thin"/>
    <div class="time_restrictions">
        <button class="button" type="button" event-click="eveningRestrictionsPreset">Evening</button>
        <button class="button" type="button" event-click="officeRestrictionsPreset">Office</button>
        <button class="button" type="button" event-click="noneRestrictionsPreset">None</button>

        @php
            $days = [["monday", "Monday"], ["tuesday", "Tuesday"], ["wednesday", "Wednesday"], ["thursday", "Thursday"], ["friday", "Friday"], ["saturday", "Saturday"], ["sunday", "Sunday"]];
        @endphp
        @php
            $seed = str_shuffle("timerestrictionsui");
        @endphp
        @foreach($days as $day)
            <div class="restrictions-row">
                <div class="checkbox">
                    <input type="checkbox" id="{{$seed}}-{{$day[0]}}_checkbox" class="{{$day[0]}}_checkbox"
                           event-change="m_timeRestrictionsUI.timeRestrictions.{{$day[0]}}.generateInternetLabel"
                           value-bind="m_timeRestrictionsUI.timeRestrictions.{{$day[0]}}.RestrictionsEnabled"/>
                    <label for="{{$seed}}-{{$day[0]}}_checkbox">{{$day[1]}}</label>
                </div>
                <div class="slider {{$day[0]}}" data-caption="{{$day[1]}}"></div>
            </div>
            <div style="clear: both;"></div>
            <div class="restrictions-label" text-bind="timeRestrictions.{{$day[0]}}.internetLabel"></div>
        @endforeach
    </div>
</div>