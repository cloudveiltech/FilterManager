<rss xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle" xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0">
    <channel>
        <title>{{ $app_name }} Update</title>
        <description>New version available.</description>
        <language>en</language>
        @foreach ($channels AS $channel)
            <item>
                <title>{{ $channel['release'] }} {{ $channel['version_number'] }}</title>
                <description>
                    <![CDATA[
                    <ul>
                        @foreach ($changes AS $change)
                            <li>{{ $change }}</li>
                        @endforeach
                    </ul>
                    ]]>
                </description>
                <pubDate>{{ $date }}</pubDate>
                @if($is_web_installer)
                    <enclosure
                            channel="{{ $channel['release'] }}"
                            url="{{ url('/') }}/update/releases/{{ urlencode($activation_id) }}/{{ $file_name }}{{ $file_ext }}" sparkle:os="{{ $os_name }}"
                            sparkle:installerArguments="/quiet /norestart" sparkle:version="{{ $channel['version_number'] }}"
                            sparkle:edSignature="{{ $channel["signature"] }}"
                            length="0"
                            type="application/octet-stream"/>
                @else
                    <enclosure
                            channel="{{ $channel['release'] }}"
                            url="{{ url('/') }}/update/releases/{{ urlencode($activation_id) }}/{{ $file_name }}-{{ $channel['version_number'] }}-{{ $platform }}{{ $file_ext }}" sparkle:os="{{ $os_name }}"
                            sparkle:installerArguments="/quiet /norestart" sparkle:version="{{ $channel['version_number'] }}"
                            sparkle:edSignature="{{ $channel["signature"] }}"
                            length="0"
                            type="application/octet-stream"/>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
