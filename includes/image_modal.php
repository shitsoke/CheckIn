<?php
// simple image modal used by pages to enlarge images
?>
<!-- Image enlarge modal -->
<div id="imageModal" class="modal" tabindex="-1" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.9);align-items:center;justify-content:center;">
  <div style="max-width:95%;max-height:95%;position:relative;">
    <div style="display:flex;justify-content:flex-end;margin-bottom:6px;gap:6px;">
      <button id="imgZoomOut" class="btn btn-sm btn-light">−</button>
      <button id="imgZoomReset" class="btn btn-sm btn-light">Reset</button>
      <button id="imgZoomIn" class="btn btn-sm btn-light">+</button>
      <button id="imageModalClose" class="btn btn-sm btn-secondary">Close</button>
    </div>
    <div id="imageModalInner" style="overflow:hidden;touch-action:none;cursor:grab;border-radius:4px;">
      <img id="imageModalImg" src="" style="display:block;max-width:100%;height:auto;transform-origin:0 0;">
    </div>
  </div>
</div>
<script>
  (function(){
    var modal = document.getElementById('imageModal');
    var img = document.getElementById('imageModalImg');
    var inner = document.getElementById('imageModalInner');
    var scale = 1, tx = 0, ty = 0;
    var isPanning = false, startX=0, startY=0;
    function setTransform(){ img.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')'; }
    function showModal(src){ scale = 1; tx = 0; ty = 0; setTransform(); img.src = src; modal.style.display = 'flex'; }
    function hideModal(){ modal.style.display='none'; img.src=''; }
    // click to enlarge trigger
    document.addEventListener('click', function(e){
      var t = e.target;
      if (t.classList && t.classList.contains('click-enlarge')) { e.preventDefault(); showModal(t.getAttribute('data-src') || t.src); }
      if (t.id === 'imageModalClose') hideModal();
    });
    // wheel zoom
    modal.addEventListener('wheel', function(e){ if (modal.style.display!=='flex') return; e.preventDefault(); var delta = -e.deltaY; var factor = delta>0?1.1:0.9; scale = Math.max(0.2, Math.min(8, scale * factor)); setTransform(); }, { passive:false });
    // pointer for pan
    inner.addEventListener('pointerdown', function(e){ if (scale <= 1) return; isPanning = true; startX = e.clientX - tx; startY = e.clientY - ty; inner.setPointerCapture(e.pointerId); inner.style.cursor='grabbing'; });
    inner.addEventListener('pointermove', function(e){ if (!isPanning) return; tx = e.clientX - startX; ty = e.clientY - startY; setTransform(); });
    inner.addEventListener('pointerup', function(e){ isPanning = false; inner.releasePointerCapture(e.pointerId); inner.style.cursor='grab'; });
    inner.addEventListener('pointercancel', function(){ isPanning = false; inner.style.cursor='grab'; });
    // buttons
    document.getElementById('imgZoomIn').addEventListener('click', function(){ scale = Math.min(8, scale * 1.2); setTransform(); });
    document.getElementById('imgZoomOut').addEventListener('click', function(){ scale = Math.max(0.2, scale / 1.2); setTransform(); });
    document.getElementById('imgZoomReset').addEventListener('click', function(){ scale=1; tx=0; ty=0; setTransform(); });
    // Esc to close
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') hideModal(); });
    // click backdrop to close
    modal.addEventListener('click', function(e){ if (e.target === modal) hideModal(); });
  })();
</script>
