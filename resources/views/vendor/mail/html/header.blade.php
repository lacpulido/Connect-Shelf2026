@props(['url'])

<tr>
<td class="header" style="padding: 25px 0; text-align: center;">
    <a href="{{ $url }}" style="text-decoration: none; display: inline-block;">

        <!-- LOGO (FROM NAVBAR) -->
        <img 
            src="{{ config('app.url') . '/images/coonect.png' }}"
            alt="Connect-Shelf Logo"
            style="height: 80px; width: 100px; object-fit: contain; display: block; margin: 0 auto 8px auto;"
        >

        <!-- TITLE -->
        <div style="font-size: 26px; font-weight: bold; line-height: 1;">
            <span style="color: #0C4B05;">Connect-</span>
            <span style="color: #FFCD00;">Shelf</span>
        </div>

     
    </a>
</td>
</tr>