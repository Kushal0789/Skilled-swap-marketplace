/**
 * User Profile & Skill Management Controller
 * Profile Editor, Avatar Upload & Skill CRUD
 */

document.addEventListener('DOMContentLoaded', () => {
    const profileForm = document.getElementById('profile-info-form');
    const avatarInput = document.getElementById('avatar-file-input');
    const avatarPreviewImg = document.getElementById('profile-avatar-preview');
    const addOfferedSkillBtn = document.getElementById('btn-add-offered-skill');
    const addWantedSkillBtn = document.getElementById('btn-add-wanted-skill');

    // 1. Profile Info Form Submit
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = profileForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Saving...';

            const formData = new FormData(profileForm);

            try {
                const response = await fetch('api/users/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });
                const res = await response.json();

                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Changes';

                if (res.success) {
                    window.showToast('Profile updated successfully!', 'success');
                    if (res.data.avatar_url && avatarPreviewImg) {
                        avatarPreviewImg.src = res.data.avatar_url;
                        const navAvatar = document.querySelector('.nav-avatar-img');
                        if (navAvatar) navAvatar.src = res.data.avatar_url;
                    }
                } else {
                    window.showToast(res.message || 'Failed to update profile.', 'error');
                }
            } catch (err) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Changes';
                window.showToast('Network error while saving profile.', 'error');
            }
        });
    }

    // Avatar preview when selected
    if (avatarInput && avatarPreviewImg) {
        avatarInput.addEventListener('change', () => {
            const file = avatarInput.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    window.showToast('Image file size must be less than 2MB.', 'error');
                    avatarInput.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    avatarPreviewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 2. Add Skill Modals
    if (addOfferedSkillBtn) {
        addOfferedSkillBtn.addEventListener('click', () => openAddSkillModal('OFFER'));
    }

    if (addWantedSkillBtn) {
        addWantedSkillBtn.addEventListener('click', () => openAddSkillModal('WANT'));
    }

    async function openAddSkillModal(type = 'OFFER') {
        // Fetch skills catalog
        const res = await window.apiFetch('api/skills/list.php');
        const skills = (res.success && res.data.skills) ? res.data.skills : [];
        const categories = (res.success && res.data.categories) ? res.data.categories : ['General'];

        const skillsOptions = skills.map(s => `
            <option value="${s.id}">[${escapeHtml(s.category)}] ${escapeHtml(s.name)}</option>
        `).join('');

        const categoryOptions = categories.map(c => `
            <option value="${escapeHtml(c)}">${escapeHtml(c)}</option>
        `).join('');

        const typeLabel = type === 'OFFER' ? 'Skill You Can Offer & Teach' : 'Skill You Want to Learn';

        const modalHtml = `
            <form id="add-skill-form">
                <input type="hidden" name="skill_type" value="${type}">

                <div class="form-group">
                    <label class="form-label">Select Skill from Catalog</label>
                    <select name="skill_id" id="skill-catalog-select" class="form-select">
                        <option value="">-- Choose from existing skills --</option>
                        ${skillsOptions}
                    </select>
                </div>

                <div style="text-align: center; margin: 0.5rem 0; color: var(--text-muted); font-size: 0.85rem;">
                    &mdash; OR CREATE A NEW SKILL &mdash;
                </div>

                <div class="form-group">
                    <label class="form-label">Custom Skill Name</label>
                    <input type="text" name="new_skill_name" id="new-skill-input" class="form-input" placeholder="e.g. Kotlin, Origami, Docker">
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        ${categoryOptions}
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Proficiency Level</label>
                    <select name="proficiency" class="form-select" required>
                        <option value="Beginner">Beginner - Basic understanding</option>
                        <option value="Intermediate" selected>Intermediate - Practical experience</option>
                        <option value="Advanced">Advanced - In-depth expertise</option>
                        <option value="Expert">Expert - Professional / Master</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description / Topics covered (Optional)</label>
                    <textarea name="description" class="form-textarea" placeholder="Describe what you can share or what specifically you want to learn..."></textarea>
                </div>

                <div class="modal-footer" style="margin: 1.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Skill to Profile</button>
                </div>
            </form>
        `;

        window.openModal(`Add ${typeLabel}`, modalHtml);

        const form = document.getElementById('add-skill-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            const payload = {
                skill_id: form.querySelector('#skill-catalog-select').value,
                new_skill_name: form.querySelector('#new-skill-input').value.trim(),
                category: form.querySelector('[name="category"]').value,
                skill_type: type,
                proficiency: form.querySelector('[name="proficiency"]').value,
                description: form.querySelector('[name="description"]').value.trim()
            };

            const addRes = await window.apiFetch('api/skills/add.php', {
                method: 'POST',
                body: payload
            });

            if (addRes.success) {
                window.closeModal();
                window.showToast(addRes.message, 'success');
                setTimeout(() => window.location.reload(), 600);
            } else {
                submitBtn.disabled = false;
                window.showToast(addRes.message || 'Failed to add skill.', 'error');
            }
        });
    }

    // 3. Edit & Delete Skill Handlers on Skill Badges
    document.querySelectorAll('.btn-delete-skill').forEach(btn => {
        btn.addEventListener('click', async () => {
            const skillId = btn.getAttribute('data-id');
            const skillName = btn.getAttribute('data-name');

            if (!confirm(`Are you sure you want to remove "${skillName}" from your profile?`)) return;

            const res = await window.apiFetch('api/skills/delete.php', {
                method: 'POST',
                body: { user_skill_id: skillId }
            });

            if (res.success) {
                window.showToast(res.message, 'success');
                btn.closest('.skill-badge-item').remove();
            } else {
                window.showToast(res.message, 'error');
            }
        });
    });

    document.querySelectorAll('.btn-edit-skill').forEach(btn => {
        btn.addEventListener('click', () => {
            const skillId = btn.getAttribute('data-id');
            const skillName = btn.getAttribute('data-name');
            const proficiency = btn.getAttribute('data-proficiency');
            const description = btn.getAttribute('data-description') || '';

            openEditSkillModal(skillId, skillName, proficiency, description);
        });
    });

    function openEditSkillModal(id, name, proficiency, description) {
        const modalHtml = `
            <form id="edit-skill-form">
                <input type="hidden" name="user_skill_id" value="${id}">
                <p style="font-weight: 600; margin-bottom: 1.25rem;">Skill: ${escapeHtml(name)}</p>

                <div class="form-group">
                    <label class="form-label">Proficiency Level</label>
                    <select name="proficiency" class="form-select" required>
                        <option value="Beginner" ${proficiency === 'Beginner' ? 'selected' : ''}>Beginner</option>
                        <option value="Intermediate" ${proficiency === 'Intermediate' ? 'selected' : ''}>Intermediate</option>
                        <option value="Advanced" ${proficiency === 'Advanced' ? 'selected' : ''}>Advanced</option>
                        <option value="Expert" ${proficiency === 'Expert' ? 'selected' : ''}>Expert</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description / Notes</label>
                    <textarea name="description" class="form-textarea">${escapeHtml(description)}</textarea>
                </div>

                <div class="modal-footer" style="margin: 1.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="window.closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Skill</button>
                </div>
            </form>
        `;

        window.openModal(`Edit ${name}`, modalHtml);

        const form = document.getElementById('edit-skill-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            const res = await window.apiFetch('api/skills/update.php', {
                method: 'POST',
                body: {
                    user_skill_id: id,
                    proficiency: form.querySelector('[name="proficiency"]').value,
                    description: form.querySelector('[name="description"]').value.trim()
                }
            });

            if (res.success) {
                window.closeModal();
                window.showToast(res.message, 'success');
                setTimeout(() => window.location.reload(), 600);
            } else {
                submitBtn.disabled = false;
                window.showToast(res.message, 'error');
            }
        });
    }
});
