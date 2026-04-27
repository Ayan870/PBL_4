/* ===== charts.js — Admin Dashboard Charts using Canvas ===== */
/* Pure vanilla JS bar chart — no external library needed */

document.addEventListener('DOMContentLoaded', function () {
  drawDeptChart();
});

function drawDeptChart() {
  const canvas = document.getElementById('deptChart');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');

  // Make it fill the container
  const container = canvas.parentElement;
  canvas.width  = container.clientWidth  || 560;
  canvas.height = container.clientHeight || 220;

  const W = canvas.width;
  const H = canvas.height;

  // Data (inject later from backend if needed)
  const fallback = {
    departments: ['CS', 'SE', 'EE', 'ME', 'BA'],
    approved:    [0, 0, 0, 0, 0],
    pending:     [0, 0, 0, 0, 0],
    rejected:    [0, 0, 0, 0, 0],
  };
  const injected = (window.PBL_DEPT_CHART_DATA && typeof window.PBL_DEPT_CHART_DATA === 'object')
    ? window.PBL_DEPT_CHART_DATA
    : null;

  const departments = Array.isArray(injected?.departments) ? injected.departments : fallback.departments;
  const approved    = Array.isArray(injected?.approved)    ? injected.approved    : fallback.approved;
  const pending     = Array.isArray(injected?.pending)     ? injected.pending     : fallback.pending;
  const rejected    = Array.isArray(injected?.rejected)    ? injected.rejected    : fallback.rejected;

  const colors = {
    approved: '#6C63FF',
    pending:  '#f59e0b',
    rejected: '#ef4444'
  };

  const barGroups = departments.length;
  const barsPerGroup = 3;
  const groupGap  = 28;
  const barWidth  = 18;
  const paddingL  = 44;
  const paddingR  = 16;
  const paddingT  = 20;
  const paddingB  = 40;
  const chartW    = W - paddingL - paddingR;
  const chartH    = H - paddingT - paddingB;
  const groupW    = chartW / barGroups;

  const maxVal = Math.max(...approved, ...pending, ...rejected) + 5;

  // Clear
  ctx.clearRect(0, 0, W, H);

  // Draw grid lines & Y labels
  ctx.font      = '11px Inter, system-ui, sans-serif';
  ctx.fillStyle = '#8892a4';
  ctx.textAlign = 'right';

  const gridLines = 5;
  for (let i = 0; i <= gridLines; i++) {
    const value = Math.round((maxVal / gridLines) * (gridLines - i));
    const y     = paddingT + (chartH / gridLines) * i;

    // Grid line
    ctx.strokeStyle = 'rgba(42,49,71,0.8)';
    ctx.lineWidth   = 1;
    ctx.setLineDash([4, 4]);
    ctx.beginPath();
    ctx.moveTo(paddingL, y);
    ctx.lineTo(W - paddingR, y);
    ctx.stroke();
    ctx.setLineDash([]);

    // Y label
    ctx.fillText(value, paddingL - 6, y + 4);
  }

  // Draw bars
  departments.forEach((dept, gi) => {
    const groupX    = paddingL + gi * groupW;
    const totalBarsW = barsPerGroup * barWidth + (barsPerGroup - 1) * 4;
    const startX    = groupX + (groupW - totalBarsW) / 2;

    const series = [approved[gi], pending[gi], rejected[gi]];
    const seriesColors = [colors.approved, colors.pending, colors.rejected];

    series.forEach((val, bi) => {
      const barH = (val / maxVal) * chartH;
      const x    = startX + bi * (barWidth + 4);
      const y    = paddingT + chartH - barH;

      // Bar with rounded top
      const r = Math.min(4, barH / 2);
      ctx.fillStyle = seriesColors[bi];

      // Gradient
      const grad = ctx.createLinearGradient(0, y, 0, y + barH);
      grad.addColorStop(0, seriesColors[bi]);
      grad.addColorStop(1, seriesColors[bi] + '88');
      ctx.fillStyle = grad;

      ctx.beginPath();
      if (barH > 0) {
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + barWidth - r, y);
        ctx.quadraticCurveTo(x + barWidth, y, x + barWidth, y + r);
        ctx.lineTo(x + barWidth, y + barH);
        ctx.lineTo(x, y + barH);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
      }
      ctx.closePath();
      ctx.fill();

      // Value on top
      if (val > 0) {
        ctx.fillStyle = '#e2e8f0';
        ctx.font      = '10px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(val, x + barWidth / 2, y - 4);
      }
    });

    // X label
    ctx.fillStyle = '#8892a4';
    ctx.font      = '11px Inter, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(dept, groupX + groupW / 2, H - paddingB + 18);
  });

  // Resize observer to redraw on container resize
  if (typeof ResizeObserver !== 'undefined') {
    const ro = new ResizeObserver(() => {
      canvas.width  = container.clientWidth;
      canvas.height = container.clientHeight;
      drawDeptChart();
    });
    ro.observe(container);
  }
}

/* ===== Donut / Pie chart helper (optional future use) ===== */
function drawDonut(canvasId, data, colors, labels) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width = canvas.parentElement.clientWidth || 200;
  const H = canvas.height = W;
  const cx = W / 2, cy = H / 2;
  const outerR = (W / 2) * 0.75;
  const innerR = outerR * 0.55;

  const total = data.reduce((a, b) => a + b, 0);
  let startAngle = -Math.PI / 2;

  ctx.clearRect(0, 0, W, H);

  data.forEach((val, i) => {
    const slice = (val / total) * 2 * Math.PI;
    ctx.beginPath();
    ctx.moveTo(cx, cy);
    ctx.arc(cx, cy, outerR, startAngle, startAngle + slice);
    ctx.closePath();
    ctx.fillStyle = colors[i];
    ctx.fill();
    startAngle += slice;
  });

  // Donut hole
  ctx.beginPath();
  ctx.arc(cx, cy, innerR, 0, 2 * Math.PI);
  ctx.fillStyle = getComputedStyle(document.body).getPropertyValue('--card') || '#1e2435';
  ctx.fill();

  // Center total
  ctx.fillStyle = '#e2e8f0';
  ctx.font = `bold ${Math.floor(W * 0.14)}px Inter, sans-serif`;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(total, cx, cy);
}
