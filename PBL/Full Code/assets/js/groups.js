/* ===== groups.js — Database-backed group management ===== */

const PBL_MAX_GROUP_MEMBERS = 3;

function el(id) { return document.getElementById(id); }

async function fetchAPI(url, body = null) {
  const options = {
    method: body ? 'POST' : 'GET',
    headers: { 'Content-Type': 'application/json' }
  };
  if (body) options.body = JSON.stringify(body);
  
  try {
    const res = await fetch(url, options);
    return await res.json();
  } catch (err) {
    console.error("API Error:", err);
    return { success: false, message: "Network error." };
  }
}

function renderMyGroup(group) {
  const box = el('myGroupBox');
  if (!box) return;

  if (!group) {
    box.innerHTML = `
      <div class="text-center py-3">
        <div class="text-muted small mb-3">
          You are not in a group yet. You can either start your own group or wait for an invitation.
        </div>
        <button class="btn btn-sm btn-primary" onclick="createMyGroup()">
          <i class="bi bi-plus-lg me-1"></i> Start a Group
        </button>
      </div>`;
    return;
  }

  const members = (group.members || []).filter(m => m.invite_status === 'accepted');
  const pending = (group.members || []).filter(m => m.invite_status === 'pending');

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
        <li class="list-group-item px-0 d-flex justify-content-between bg-transparent">
          <span>${m.name} <span class="text-muted">(${m.roll_number})</span></span>
          <span class="badge bg-${m.role === 'leader' ? 'primary' : 'light text-dark'}">${m.role}</span>
        </li>`).join('') || `<li class="list-group-item px-0 text-muted">No members yet.</li>`}
    </ul>
    ${pending.length ? `
      <div class="small fw-semibold mt-3 mb-1">Pending Invites (sent)</div>
      <ul class="list-group list-group-flush small">
        ${pending.map(m => `
          <li class="list-group-item px-0 text-muted bg-transparent">
            ${m.name} <span class="text-muted">(${m.roll_number})</span> — pending
          </li>`).join('')}
      </ul>` : '' }
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
    <div class="border border-secondary border-opacity-25 rounded p-3 mb-2 bg-dark bg-opacity-50">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="text-white fw-bold small mb-1">${i.from_name} <span class="text-secondary fw-normal">(${i.from_roll || ''})</span></div>
          <div class="text-secondary small">Invited you to: <span class="text-white fw-semibold">${i.group_name}</span></div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-success rounded-pill px-3" onclick="respondInvite(${i.group_id}, 'accept')">Accept</button>
          <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="respondInvite(${i.group_id}, 'reject')">Reject</button>
        </div>
      </div>
    </div>
  `).join('');
}

async function loadGroupData() {
  const res = await fetchAPI("../../api/student/get_group_info.php");
  if (res.success) {
    renderMyGroup(res.group);
    renderIncoming(res.invites);
  }
}

async function createMyGroup() {
  const name = prompt("Enter Group Name (Optional):", "");
  if (name === null) return; // cancelled
  
  const res = await fetchAPI("../../api/student/create_group.php", { name: name });
  if (res.success) {
    if (typeof showToast === 'function') showToast(res.message, 'success');
    else alert(res.message);
    loadGroupData();
  } else {
    alert(res.message);
  }
}

async function sendInvite() {
  const input = el('inviteRollInput');
  const roll = (input?.value || '').trim().toUpperCase();
  if (!roll) return;

  const btn = el('sendInviteBtn');
  if (btn) btn.disabled = true;

  // 1. Ensure we have a group and check capacity
  const groupInfo = await fetchAPI("../../api/student/get_group_info.php");
  
  if (groupInfo.success && groupInfo.group) {
    const activeMembers = (groupInfo.group.members || []).filter(m => m.invite_status === 'accepted' || m.invite_status === 'pending');
    if (activeMembers.length >= PBL_MAX_GROUP_MEMBERS) {
      alert("Group is full! A group can have a maximum of 3 members including pending invitations.");
      if (btn) btn.disabled = false;
      return;
    }
  }

  if (groupInfo.success && !groupInfo.group) {
    // Create one
    const createRes = await fetchAPI("../../api/student/create_group.php", { name: "" });
    if (!createRes.success) {
      alert(createRes.message);
      if (btn) btn.disabled = false;
      return;
    }
  }

  // 2. Send invite
  const res = await fetchAPI("../../api/student/handle_invite.php", {
    action: "invite",
    roll_number: roll
  });

  if (res.success) {
    if (typeof showToast === 'function') showToast(res.message, 'success');
    else alert(res.message);
    if (input) input.value = '';
    loadGroupData();
  } else {
    alert(res.message);
  }

  if (btn) btn.disabled = false;
}

window.respondInvite = async function(groupId, action) {
  const res = await fetchAPI("../../api/student/handle_invite.php", {
    action: "respond",
    group_id: groupId,
    response: action
  });

  if (res.success) {
    if (typeof showToast === 'function') showToast(res.message, 'success');
    else alert(res.message);
    loadGroupData();
  } else {
    alert(res.message);
  }
};

document.addEventListener('DOMContentLoaded', () => {
  const role = sessionStorage.getItem('pbl_role') || '';
  // Note: sessionStorage might have 'pbl_role' or 'user_role' depending on how auth.js was written.
  // In index.php we set pbl_role.
  
  const form = el('inviteForm');
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      sendInvite();
    });
  }
  loadGroupData();
});
