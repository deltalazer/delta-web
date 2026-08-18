{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
<style>
    .delta-admin__notice { border-left: 3px solid #4caf50; padding: 10px 16px; margin-bottom: 20px; background: rgba(76,175,80,0.12); border-radius: 4px; }
    .delta-admin__error { border-left: 3px solid #b92e35; padding: 10px 16px; margin-bottom: 20px; background: rgba(185,46,53,0.12); border-radius: 4px; }
    .delta-admin__card { border: 1px solid rgba(128,128,128,0.25); border-radius: 8px; padding: 20px 24px; margin-bottom: 20px; }
    .delta-admin__card-title { font-size: 18px; font-weight: 600; margin: 0 0 14px; }
    .delta-admin__row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px; }
    .delta-admin__facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .delta-admin__fact-label { font-size: 11px; text-transform: uppercase; opacity: 0.6; letter-spacing: 0.05em; }
    .delta-admin__fact-value { font-size: 15px; font-weight: 600; }
    .delta-admin input[type=text], .delta-admin input[type=number], .delta-admin select {
        background: rgba(0,0,0,0.25); border: 1px solid rgba(128,128,128,0.35); border-radius: 4px;
        padding: 7px 10px; color: inherit; font: inherit;
    }
    .delta-admin button { border: 0; border-radius: 4px; padding: 8px 16px; font: inherit; font-weight: 600; cursor: pointer; background: #b92e35; color: #fff; }
    .delta-admin button:hover { background: #9f282f; }
    .delta-admin button.is-quiet { background: rgba(128,128,128,0.3); }
    .delta-admin button.is-quiet:hover { background: rgba(128,128,128,0.45); }
    .delta-admin__tag { display: inline-block; background: rgba(185,46,53,0.25); border-radius: 3px; padding: 2px 8px; margin: 0 4px 4px 0; font-size: 13px; }
</style>

@if (session('delta_notice'))
    <div class="delta-admin__notice">{{ session('delta_notice') }}</div>
@endif

@if (session('delta_error'))
    <div class="delta-admin__error">{{ session('delta_error') }}</div>
@endif
