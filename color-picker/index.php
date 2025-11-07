<?php
// color-picker/index.php
// Simple, self-contained color picker tool with preview, palette, conversions and export/copy helpers.
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Color Picker</title>
  <style>
    :root{--bg:#f5f7fb;--card:#fff;--muted:#6b7280}
    body{font-family:Inter,Segoe UI,Arial,sans-serif;background:var(--bg);margin:0;padding:18px;color:#111}
    .wrap{max-width:980px;margin:0 auto}
    .card{background:var(--card);padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(16,24,40,.06);margin-bottom:16px}
    h1{margin:0 0 8px;font-size:20px}
    .grid{display:grid;grid-template-columns:1fr 320px;gap:16px}
    .controls label{display:block;font-weight:600;margin:8px 0 6px}
    input[type=color]{width:56px;height:56px;border:0;padding:0;margin-right:8px;vertical-align:middle}
    .row{display:flex;align-items:center;gap:8px}
    .field{display:flex;gap:8px;align-items:center}
    .mono{font-family:ui-monospace,monospace;font-size:13px;background:#f3f4f6;padding:6px 8px;border-radius:6px}
    .preview{height:160px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px}
    .palette{display:flex;gap:8px;flex-wrap:wrap}
    .swatch{width:36px;height:36px;border-radius:6px;border:1px solid rgba(0,0,0,.06);cursor:pointer}
    button{background:#111827;color:#fff;border:0;padding:8px 10px;border-radius:6px;cursor:pointer}
    .muted{color:var(--muted);font-size:13px}
    .small{font-size:13px;padding:6px 8px}
    .copy-btn{background:#efefef;color:#111;border:0;padding:6px 8px;border-radius:6px;cursor:pointer}
    .export{display:flex;flex-direction:column;gap:8px}
    .history{display:flex;gap:8px;flex-wrap:wrap}
    pre{background:#0b0b0b;color:#dcdcdc;padding:12px;border-radius:6px;overflow:auto}
    @media (max-width:900px){.grid{grid-template-columns:1fr;}}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>Color Picker</h1>
      <div class="muted">Choose a color, view conversions (HEX / RGB / HSL), add to palette, copy values or export CSS variables.</div>
    </div>

    <div class="grid">
      <div class="card controls">
        <label for="colorInput">Pick a color</label>
        <div class="row">
          <input id="colorInput" type="color" value="#2563eb" aria-label="Select color">
          <div style="flex:1">
            <div class="row" style="align-items:center;justify-content:space-between">
              <div class="field">
                <div id="hex" class="mono">#2563EB</div>
                <button id="copyHex" class="copy-btn" title="Copy hex">Copy</button>
              </div>
              <div class="field">
                <div id="rgb" class="mono">rgb(37,99,235)</div>
                <button id="copyRgb" class="copy-btn" title="Copy rgb">Copy</button>
              </div>
            </div>
            <div style="height:8px"></div>
            <div class="row" style="align-items:center;justify-content:space-between">
              <div class="field">
                <div id="hsl" class="mono">hsl(217,89%,53%)</div>
                <button id="copyHsl" class="copy-btn" title="Copy hsl">Copy</button>
              </div>
              <div class="field">
                <button id="addPalette" class="small">Add to palette</button>
                <button id="clearPalette" class="small">Clear palette</button>
              </div>
            </div>
          </div>
        </div>

        <label style="margin-top:14px">Palette</label>
        <div class="palette" id="palette"></div>

        <label style="margin-top:12px">History</label>
        <div class="history" id="history"></div>

        <div style="height:12px"></div>
        <div class="export">
          <label>Export / copy</label>
          <div class="row">
            <button id="copyCss">Copy CSS variable</button>
            <button id="downloadPng">Download swatch (PNG)</button>
            <button id="copyRgbList">Copy CSS rgb()</button>
          </div>
        </div>
      </div>

      <div class="card">
        <label>Preview</label>
        <div id="preview" class="preview" style="background:#2563eb">#2563EB</div>

        <h3 style="margin-top:14px">Usage snippet</h3>
        <pre id="usage">:root { --color-primary: #2563eb; }</pre>

        <h3 style="margin-top:12px">Generated CSS variables</h3>
        <pre id="cssvars">--color-1: #2563eb;</pre>
      </div>
    </div>
  </div>

<script>
// Conversion helpers
function hexToRgb(hex){
  hex = hex.replace('#','');
  if(hex.length===3) hex = hex.split('').map(c=>c+c).join('');
  const bigint = parseInt(hex,16);
  const r=(bigint>>16)&255; const g=(bigint>>8)&255; const b=bigint&255;
  return [r,g,b];
}
function rgbToHex(r,g,b){
  return '#'+[r,g,b].map(x=>x.toString(16).padStart(2,'0')).join('');
}
function rgbToHsl(r,g,b){
  r/=255;g/=255;b/=255; const max=Math.max(r,g,b), min=Math.min(r,g,b);
  let h,s,l=(max+min)/2; if(max===min){h=s=0;}else{const d=max-min; s=l>0.5?d/(2-max-min):d/(max+min);
    switch(max){case r:h=(g-b)/d+(g<b?6:0);break;case g:h=(b-r)/d+2;break;case b:h=(r-g)/d+4;break;} h/=6;}
  return [Math.round(h*360), Math.round(s*100), Math.round(l*100)];
}
function hslToRgb(h,s,l){
  h/=360; s/=100; l/=100; if(s===0){ const v=Math.round(l*255); return [v,v,v]; }
  const hue2rgb=(p,q,t)=>{ if(t<0)t+=1; if(t>1)t-=1; if(t<1/6) return p+(q-p)*6*t; if(t<1/2) return q; if(t<2/3) return p+(q-p)*(2/3-t)*6; return p; };
  const q = l<0.5 ? l*(1+s) : l+s-l*s; const p=2*l-q;
  const r=hue2rgb(p,q,h+1/3); const g=hue2rgb(p,q,h); const b=hue2rgb(p,q,h-1/3);
  return [Math.round(r*255),Math.round(g*255),Math.round(b*255)];
}
function toUpperHex(h){ return h.toUpperCase(); }

// UI wiring
const colorInput=document.getElementById('colorInput');
const hexEl=document.getElementById('hex');
const rgbEl=document.getElementById('rgb');
const hslEl=document.getElementById('hsl');
const preview=document.getElementById('preview');
const usage=document.getElementById('usage');
const cssvars=document.getElementById('cssvars');
const paletteEl=document.getElementById('palette');
const historyEl=document.getElementById('history');
let palette=[]; let history=[];

function updateUI(hex){
  const [r,g,b]=hexToRgb(hex);
  const [h,s,l]=rgbToHsl(r,g,b);
  const hexU = hex.toUpperCase();
  hexEl.textContent = hexU;
  rgbEl.textContent = `rgb(${r}, ${g}, ${b})`;
  hslEl.textContent = `hsl(${h}, ${s}%, ${l}%)`;
  preview.style.background = hex;
  preview.textContent = hexU;
  usage.textContent = `:root { --color-primary: ${hex}; }`;
  cssvars.textContent = `--color-1: ${hex};`;
}

function addToPalette(hex){ if(!palette.includes(hex)){ palette.unshift(hex); if(palette.length>24) palette.pop(); renderPalette(); addHistory(hex); } }
function addHistory(hex){ history.unshift(hex); if(history.length>20) history.length=20; renderHistory(); }

function renderPalette(){ paletteEl.innerHTML=''; palette.forEach(h=>{ const d=document.createElement('div'); d.className='swatch'; d.style.background=h; d.title=h; d.tabIndex=0; d.addEventListener('click',()=>{ colorInput.value=h; updateUI(h); addHistory(h); }); d.addEventListener('keydown',(e)=>{ if(e.key==='Enter') { colorInput.value=h; updateUI(h); addHistory(h); } }); paletteEl.appendChild(d); }); }
function renderHistory(){ historyEl.innerHTML=''; history.forEach(h=>{ const b=document.createElement('button'); b.className='small'; b.textContent=h; b.addEventListener('click',()=>{ colorInput.value=h; updateUI(h); }); historyEl.appendChild(b); }); }

// copy helpers
function copyText(s){ navigator.clipboard.writeText(s).then(()=>{ const orig=document.activeElement; orig && orig.blur(); }); }

document.getElementById('copyHex').addEventListener('click',()=>copyText(hexEl.textContent));
document.getElementById('copyRgb').addEventListener('click',()=>copyText(rgbEl.textContent));
document.getElementById('copyHsl').addEventListener('click',()=>copyText(hslEl.textContent));

document.getElementById('addPalette').addEventListener('click',()=>addToPalette(colorInput.value));
document.getElementById('clearPalette').addEventListener('click',()=>{ palette=[]; renderPalette(); });

document.getElementById('copyCss').addEventListener('click',()=>{ const css=`:root { --color-primary: ${hexEl.textContent}; }`; copyText(css); });
document.getElementById('copyRgbList').addEventListener('click',()=>copyText(rgbEl.textContent));

document.getElementById('downloadPng').addEventListener('click',()=>{
  // simple swatch PNG via canvas
  const c=document.createElement('canvas'); c.width=400; c.height=200; const ctx=c.getContext('2d'); ctx.fillStyle=colorInput.value; ctx.fillRect(0,0,c.width,c.height);
  c.toBlob(blob=>{ const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download='swatch.png'; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url); });
});

colorInput.addEventListener('input',e=>{ updateUI(e.target.value); });

// init
updateUI(colorInput.value);
palette=['#2563eb','#ef4444','#10b981','#f59e0b','#8b5cf6']; renderPalette(); addHistory(colorInput.value);
</script>
</body>
</html>
