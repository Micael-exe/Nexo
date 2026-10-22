@props(['name' => 'water'])
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('arrow-right')<path d="M4 12h15m-6-6 6 6-6 6"/>@break
        @case('arrow-up-right')<path d="M6 18 18 6M6 6h12v12"/>@break
        @case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
        @case('close')<path d="m6 6 12 12M6 18 18 6"/>@break
        @case('leaf')<path d="M20 4c-8-2-15 2-15 8a6 6 0 0 0 11 3c2-3 3-7 4-11Z"/><path d="M3 21 15 9M8 16l-1-6m5 2 5 1"/>@break
        @case('pin')<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2.3"/>@break
        @case('location')<path d="m21 3-6 18-4-8-8-4L21 3Z"/><path d="m11 13 5-5"/>@break
        @case('check')<path d="m5 12 4 4L19 6"/>@break
        @case('shield')<path d="M12 3 4.5 6v6c0 5 7.5 9 7.5 9s7.5-4 7.5-9V6L12 3Z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>@break
        @case('camera')<path d="M8 6 9.5 3.5h5L16 6h4a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h4Z"/><circle cx="12" cy="12.5" r="3.5"/>@break
        @case('info')<circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10h.01"/>@break
        @case('clipboard')<rect x="7" y="4" width="13" height="16" rx="2"/><path d="M7 8H4v13h12v-1M11 4V2h5v2"/>@break
        @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18m-13 5 3 3 5-5"/>@break
        @case('trash')<path d="M4 6h16M9 6V3h6v3M6 6l1 15h10l1-15M10 10v7m4-7v7"/>@break
        @case('file')<path d="M14 3H5v18h14V8l-5-5Z"/><path d="M14 3v5h5M8 12h8m-8 4h6"/>@break
        @case('chevron')<path d="m9 5 7 7-7 7"/>@break
        @case('warning')<path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v5m0 3h.01"/>@break
        @case('home')<path d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-7h6v7"/>@break
        @case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>@break
        @case('map')<path d="M9 4 3 6.5v14L9 18l6 2.5 6-2.5v-14L15 6.5 9 4Z"/><path d="M9 4v14m6-11.5v14"/>@break
        @case('block')<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>@break
        @case('plus')<path d="M12 4v16M4 12h16"/>@break
        @default<path d="M2 7c3-3 5 3 8 0s5 3 8 0 4 0 4 0M2 12c3-3 5 3 8 0s5 3 8 0 4 0 4 0M2 17c3-3 5 3 8 0s5 3 8 0 4 0 4 0"/>
    @endswitch
</svg>
