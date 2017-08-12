<rss xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle" xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0">
    <channel>
        <title>{{ $app_name }} Update</title>
        <description>New version available.</description>
        <language>en</language>
        <item>
            <title>Version {{ $version_name }}</title>
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
            <enclosure url="{{ url('/') }}/releases/{{ $file_name }}-{{ $version_number }}-{{ $platform }}.msi" length="0" sparkle:os="windows" sparkle:installerArguments="/quiet" sparkle:version="{{ $version_number }}" type="application/octet-stream"/>
        </item>
    </channel>
</rss>