 :root{--scrollbar-track:#edf2ed;--scrollbar-thumb:#799788;--scrollbar-hover:#164f40;scrollbar-color:var(--scrollbar-thumb) var(--scrollbar-track);scrollbar-width:thin}
:root[data-theme="dark"]{--scrollbar-track:#101a17;--scrollbar-thumb:#476b59;--scrollbar-hover:#88c99e}
*{scrollbar-width:thin;scrollbar-color:var(--scrollbar-thumb) var(--scrollbar-track)}
::-webkit-scrollbar{width:10px;height:10px}::-webkit-scrollbar-track{background:var(--scrollbar-track)}::-webkit-scrollbar-thumb{background:var(--scrollbar-thumb);border:2px solid var(--scrollbar-track);border-radius:10px}::-webkit-scrollbar-thumb:hover{background:var(--scrollbar-hover)}::-webkit-scrollbar-corner{background:var(--scrollbar-track)}
@media(forced-colors:active){*{scrollbar-color:auto;scrollbar-width:auto}}
