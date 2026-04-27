/* ===== groups.js — Frontend-only group requests (max 3) ===== */

const PBL_GROUPS_KEY = 'pbl_groups_v1';
const PBL_MAX_GROUP_MEMBERS = 3;

function el(id) { return document.getElementById(id); }

function groupsRead() {
  try {
    const raw = localStorage.getItem(PBL_GROUPS_KEY);
    const parsed = raw ? JSON.parse(raw) : null;
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function groupsWrite(groups) {
  localStorage.setItem(PBL_GROUPS_KEY, JSON.stringify(groups));
}

function nowIso() {
  return new Date().toISOString();
}

function studentName(roll) {
  if (typeof pblFindStudentByRoll !== 'function') return roll;
  const u = pblFindStudentByRoll(roll);
  return u?.name || roll;
}

function findMyAcceptedGroup(roll) {
  const groups = groupsRead();
  return groups.find(g => (g.members || []).some(m => m.rollNumber === roll && m.invite_status === 'accepted')) || null;
}

function isInAnyAcceptedGroup(roll) {
  return !!findMyAcceptedGroup(roll);
}

function acceptedCount(group) {
  return (group.members || []).filter(m => m.invite_status === 'accepted').length;
}

function ensureMyGroup(roll) {
  const groups = groupsRead();
  let group = groups.find(g => g.created_by_roll === roll) || null;

  // If I created a group but I'm not accepted (shouldn't happen), fix it.
  if (group && !(group.members || []).some(m => m.rollNumber === roll && m.invite_status === 'accepted')) {
    group.members = group.members || [];
    group.members.push({ rollNumber: roll, role: 'leader', invite_status: 'accepted', joined_at: nowIso() });
  }

  // If I haven't created a group yet, create one (I become leader).
  if (!group) {
    group = {
      id: Date.now(),
      name: `Group ${String(roll).slice(-3)}`,
      status: 'forming',
      created_by_roll: roll,
      created_at: nowIso(),
      members: [{ rollNumber: roll, role: 'leader', invite_status: 'accepted', joined_at: nowIso() }]
    };
    groups.unshift(group);
  }

  groupsWrite(groups);
  return group;
}

function renderMyGroup(group, myRoll) {
  const box = el('myGroupBox');
  if (!box) return;

  if (!group) {
    box.innerHTML = `
      <div class="text-muted small">
        You are not in a group yet. Send a request to a classmate to start a group (max ${PBL_MAX_GROUP_MEMBERS} students).
      </div>`;
    return;
  }

  const members = (group.members || [])
    .filter(m => m.invite_status === 'accepted')
    .map(m => ({ ...m, name: studentName(m.rollNumber), roll_number: m.rollNumber }));
  const pending = (group.members || [])
    .filter(m => m.invite_status === 'pending')
    .map(m => ({ ...m, name: studentName(m.rollNumber), roll_number: m.rollNumber }));

  box.innerHTML = `
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <div class="fw-semibold">${group.name}</div>
        <div class="text-muted small">Status: <span class="badge bg-${group.status === 'active' ? 'success' : 'secondary'}">${group.status}</span></div>
      </div>
      <div class="text-muted small">${members.length}/${PBL_MAX_GROUP_MEMBERS} members</div>
    </div>
    <hr class="my-2"/>
    <div class="small fw-semibold mb-1">Members</div>
    <ul class="list-group list-group-flush small">
      ${members.map(m => `
        <li class="list-group-item px-0 d-flex justify-content-between">
          <span>${m.name} <span class="text-muted">(${m.roll_number})</span></span>
          <span class="badge bg-${m.role === 'leader' ? 'primary' : 'light text-dark'}">${m.role}</span>
        </li>`).join('') || `<li class="list-group-item px-0 text-muted">No members yet.</li>`}
    </ul>
    ${pending.length ? `
      <div class="small fw-semibold mt-3 mb-1">Pending Invites (sent)</div>
      <ul class="list-group list-group-flush small">
        ${pending.map(m => `
          <li class="list-group-item px-0 text-muted">
            ${m.name} <span class="text-muted">(${m.roll_number})</span> — pending
          </li>`).join('')}
      </ul>` : '' }
    ${group.created_by_roll === myRoll ? `<div class="form-text mt-2">Tip: if your group reaches ${PBL_MAX_GROUP_MEMBERS} accepted members, it becomes active.</div>` : '' }
  `;
}

function renderIncoming(invites) {
  const list = el('incomingInvites');
  if (!list) return;
  if (!invites || !invites.length) {
    list.innerHTML = `<div class="text-muted small">No requests right now.</div>`;
    return;
  }

  list.innerHTML = invites.map(i => `
    <div class="border rounded p-2 mb-2 bg-white">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fw-semibold small">${i.from_name} <span class="text-muted">(${i.from_roll || ''})</span></div>
          <div class="text-muted small">Invited you to: <span class="fw-semibold">${i.group_name}</span></div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-success" onclick="respondInvite(${i.group_id}, 'accept')">Accept</button>
          <button class="btn btn-sm btn-outline-danger" onclick="respondInvite(${i.group_id}, 'reject')">Reject</button>
        </div>
      </div>
    </div>
  `).join('');
}

function loadGroupData() {
  const myRoll = sessionStorage.getItem('pbl_roll_number') || '';
  const group = myRoll ? findMyAcceptedGroup(myRoll) : null;

  renderMyGroup(group, myRoll);

  const groups = groupsRead();
  const invites = groups
    .filter(g => (g.members || []).some(m => m.rollNumber === myRoll && m.invite_status === 'pending'))
    .map(g => ({
      group_id: g.id,
      group_name: g.name,
      from_roll: g.created_by_roll,
      from_name: studentName(g.created_by_roll)
    }))
    .sort((a, b) => (b.group_id - a.group_id));

  renderIncoming(invites);
}

function validateRollFormat(roll) {
  return /^SU74-BSCSM-F24-\d{3}$/.test(roll);
}

function sendInvite() {
  const myRoll = (sessionStorage.getItem('pbl_roll_number') || '').trim().toUpperCase();
  if (!myRoll) {
    alert('Please sign in as a student first.');
    return;
  }

  const input = el('inviteRollInput');
  const toRoll = (input?.value || '').trim().toUpperCase();
  if (!toRoll) return;
  if (!validateRollFormat(toRoll)) {
    alert('Invalid roll number format. Example: SU74-BSCSM-F24-005');
    return;
  }
  if (toRoll === myRoll) {
    alert('You cannot invite yourself.');
    return;
  }
  if (typeof pblFindStudentByRoll !== 'function' || !pblFindStudentByRoll(toRoll)) {
    alert('Student not found in your class.');
    return;
  }
  if (isInAnyAcceptedGroup(toRoll)) {
    alert('This student is already in a group.');
    return;
  }
  if (isInAnyAcceptedGroup(myRoll) && !findMyAcceptedGroup(myRoll)?.created_by_roll) {
    // defensive, should never happen
  }

  const btn = el('sendInviteBtn');
  if (btn) btn.disabled = true;

  const groups = groupsRead();
  let group = groups.find(g => g.created_by_roll === myRoll) || null;
  if (!group) {
    group = ensureMyGroup(myRoll);
  }

  if (acceptedCount(group) >= PBL_MAX_GROUP_MEMBERS) {
    alert(`Group is full (max ${PBL_MAX_GROUP_MEMBERS} students).`);
    if (btn) btn.disabled = false;
    return;
  }

  group.members = group.members || [];
  const already = group.members.some(m => m.rollNumber === toRoll && (m.invite_status === 'pending' || m.invite_status === 'accepted'));
  if (already) {
    alert('Invite already sent (or student already in your group).');
    if (btn) btn.disabled = false;
    return;
  }

  group.members.push({ rollNumber: toRoll, role: 'member', invite_status: 'pending', joined_at: null });

  // Persist changes
  const updated = groups.map(g => (g.id === group.id ? group : g));
  groupsWrite(updated);

  if (typeof showToast === 'function') showToast('Request sent.', 'success');
  if (input) input.value = '';
  loadGroupData();
  if (btn) btn.disabled = false;
}

window.respondInvite = function respondInvite(groupId, action) {
  const myRoll = (sessionStorage.getItem('pbl_roll_number') || '').trim().toUpperCase();
  if (!myRoll) {
    alert('Please sign in as a student first.');
    return;
  }

  const groups = groupsRead();
  const group = groups.find(g => g.id === groupId);
  if (!group) {
    alert('Invite not found.');
    return;
  }

  const member = (group.members || []).find(m => m.rollNumber === myRoll && m.invite_status === 'pending');
  if (!member) {
    alert('Invite not found.');
    return;
  }

  if (action === 'reject') {
    member.invite_status = 'rejected';
    groupsWrite(groups);
    if (typeof showToast === 'function') showToast('Request rejected.', 'success');
    loadGroupData();
    return;
  }

  // accept
  if (isInAnyAcceptedGroup(myRoll)) {
    alert('You are already in a group.');
    return;
  }
  if (acceptedCount(group) >= PBL_MAX_GROUP_MEMBERS) {
    alert(`Group is full (max ${PBL_MAX_GROUP_MEMBERS} students).`);
    return;
  }

  member.invite_status = 'accepted';
  member.joined_at = nowIso();

  // auto-reject other pending invites for me
  for (const g of groups) {
    if (g.id === group.id) continue;
    for (const m of (g.members || [])) {
      if (m.rollNumber === myRoll && m.invite_status === 'pending') {
        m.invite_status = 'rejected';
      }
    }
  }

  if (acceptedCount(group) >= PBL_MAX_GROUP_MEMBERS) {
    group.status = 'active';
  }

  groupsWrite(groups);
  if (typeof showToast === 'function') showToast('Joined group.', 'success');
  loadGroupData();
};

document.addEventListener('DOMContentLoaded', () => {
  const role = sessionStorage.getItem('pbl_role') || '';
  if (role !== 'student') return;

  const form = el('inviteForm');
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      sendInvite();
    });
  }
  loadGroupData();
});

