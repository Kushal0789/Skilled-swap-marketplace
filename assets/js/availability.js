/**
 * Availability Management Client Controller
 * Manages overall status, per-day toggles, multi-slot time editors,
 * overlap/range validation, and persistence via Fetch API.
 */

document.addEventListener('DOMContentLoaded', () => {
    const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const STATUS_LABELS = {
        available: 'Available',
        busy: 'Busy',
        unavailable: 'Not Available'
    };

    const form = document.getElementById('availability-form');
    const weeklyListEl = document.getElementById('weekly-schedule-list');
    const feedbackBanner = document.getElementById('avail-feedback-banner');
    const statusPill = document.getElementById('current-status-pill');
    const statusPillText = document.getElementById('current-status-pill-text');
    const unavailableNote = document.getElementById('status-unavailable-note');
    const summaryEl = document.getElementById('avail-save-summary');
    const saveTopBtn = document.getElementById('btn-save-avail-top');
    const saveBottomBtn = document.getElementById('btn-save-avail-bottom');
    const copyWeekdaysBtn = document.getElementById('btn-copy-mon-weekdays');
    const clearWeekBtn = document.getElementById('btn-clear-week');

    // Canonical state
    let state = {
        status: 'available',
        days: {}
    };

    DAYS.forEach(day => {
        state.days[day] = { enabled: false, slots: [] };
    });

    // Generate 30-minute time options (00:00 -> 23:30 plus 23:59)
    const BASE_TIME_OPTIONS = buildTimeOptions();

    function buildTimeOptions() {
        const opts = [];
        for (let m = 0; m < 1440; m += 30) {
            opts.push({
                value: minutesToHHMM(m),
                label: formatTime12h(minutesToHHMM(m)),
                mins: m
            });
        }
        opts.push({ value: '23:59', label: '11:59 PM', mins: 1439 });
        return opts;
    }

    function timeToMinutes(hhmm) {
        if (!hhmm || typeof hhmm !== 'string') return null;
        const m = hhmm.trim().match(/^([01]?\d|2[0-3]):([0-5]\d)$/);
        if (!m) return null;
        return parseInt(m[1], 10) * 60 + parseInt(m[2], 10);
    }

    function minutesToHHMM(totalMins) {
        const h = Math.floor(totalMins / 60);
        const m = totalMins % 60;
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    }

    function formatTime12h(hhmm) {
        const mins = timeToMinutes(hhmm);
        if (mins === null) return hhmm;
        const h24 = Math.floor(mins / 60);
        const m = mins % 60;
        const suffix = h24 >= 12 ? 'PM' : 'AM';
        let h12 = h24 % 12;
        if (h12 === 0) h12 = 12;
        return `${h12}:${String(m).padStart(2, '0')} ${suffix}`;
    }

    function buildOptionsHtml(selectedValue) {
        let options = BASE_TIME_OPTIONS;
        const selMins = timeToMinutes(selectedValue);
        if (selMins !== null && !options.some(o => o.value === selectedValue)) {
            options = [...options, {
                value: selectedValue,
                label: formatTime12h(selectedValue),
                mins: selMins
            }].sort((a, b) => a.mins - b.mins);
        }

        return options.map(opt =>
            `<option value="${opt.value}" ${opt.value === selectedValue ? 'selected' : ''}>${opt.label}</option>`
        ).join('');
    }

    function hydrateFromPayload(payload) {
        if (!payload) return;
        state.status = payload.status || 'available';
        DAYS.forEach(day => {
            const d = payload.days && payload.days[day];
            if (d && Array.isArray(d.slots) && d.slots.length > 0) {
                state.days[day] = {
                    enabled: true,
                    slots: d.slots.map(s => ({
                        start_time: String(s.start_time || '18:00').slice(0, 5),
                        end_time: String(s.end_time || '21:00').slice(0, 5)
                    }))
                };
            } else {
                state.days[day] = { enabled: false, slots: [] };
            }
        });
        syncStatusUI();
        renderWeeklySchedule();
    }

    function syncStatusUI() {
        document.querySelectorAll('.status-option-card').forEach(card => {
            const cardStatus = card.dataset.status;
            const selected = cardStatus === state.status;
            card.classList.toggle('is-selected', selected);
            const radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = selected;
        });

        if (statusPill && statusPillText) {
            statusPill.className = `avail-status-badge status-${state.status}`;
            statusPillText.textContent = STATUS_LABELS[state.status] || 'Available';
        }

        if (unavailableNote) {
            unavailableNote.classList.toggle('hidden', state.status !== 'unavailable');
        }
    }

    function validateDaySlots(day) {
        const dayObj = state.days[day];
        if (!dayObj || !dayObj.enabled || dayObj.slots.length === 0) {
            return { valid: true, invalidIndices: new Set(), message: '' };
        }

        const invalidIndices = new Set();
        let firstMsg = '';
        const parsed = [];

        dayObj.slots.forEach((slot, idx) => {
            const s = timeToMinutes(slot.start_time);
            const e = timeToMinutes(slot.end_time);
            if (s === null || e === null) {
                invalidIndices.add(idx);
                if (!firstMsg) firstMsg = 'Please enter valid start and end times.';
            } else if (e <= s) {
                invalidIndices.add(idx);
                if (!firstMsg) firstMsg = 'End time must be later than start time.';
            } else {
                parsed.push({ idx, s, e });
            }
        });

        for (let i = 0; i < parsed.length; i++) {
            for (let j = i + 1; j < parsed.length; j++) {
                const a = parsed[i];
                const b = parsed[j];
                if (a.s < b.e && b.s < a.e) {
                    invalidIndices.add(a.idx);
                    invalidIndices.add(b.idx);
                    if (!firstMsg) {
                        firstMsg = `Time slots cannot overlap (${formatTime12h(minutesToHHMM(a.s))}–${formatTime12h(minutesToHHMM(a.e))} overlaps ${formatTime12h(minutesToHHMM(b.s))}–${formatTime12h(minutesToHHMM(b.e))}).`;
                    }
                }
            }
        }

        return {
            valid: invalidIndices.size === 0,
            invalidIndices,
            message: firstMsg
        };
    }

    function updateSummary() {
        if (!summaryEl) return;
        const activeDays = DAYS.filter(d => state.days[d].enabled && state.days[d].slots.length > 0);
        const totalSlots = activeDays.reduce((acc, d) => acc + state.days[d].slots.length, 0);

        if (totalSlots === 0) {
            summaryEl.textContent = 'No weekly hours enabled yet (days set to Not Available).';
        } else {
            const dayWord = activeDays.length === 1 ? 'day' : 'days';
            const slotWord = totalSlots === 1 ? 'time slot' : 'time slots';
            summaryEl.textContent = `${activeDays.length} ${dayWord} active (${totalSlots} ${slotWord} configured).`;
        }
    }

    function renderWeeklySchedule() {
        if (!weeklyListEl) return;

        weeklyListEl.innerHTML = DAYS.map(day => {
            const dayObj = state.days[day];
            const isEnabled = dayObj.enabled && dayObj.slots.length > 0;
            const validation = validateDaySlots(day);

            const rowClasses = [
                'day-row',
                isEnabled ? 'is-available' : 'is-disabled',
                !validation.valid ? 'has-error' : ''
            ].filter(Boolean).join(' ');

            const stateText = isEnabled
                ? `${dayObj.slots.length} ${dayObj.slots.length === 1 ? 'slot' : 'slots'}`
                : 'Not Available';

            let rightHtml = '';
            if (!isEnabled) {
                rightHtml = `
                    <div class="day-unavailable-placeholder">
                        <span>Not Available</span>
                        <button type="button" class="btn-add-slot" data-action="enable-add" data-day="${day}">+ Add Hours</button>
                    </div>
                `;
            } else {
                const slotsHtml = dayObj.slots.map((slot, idx) => {
                    const isInvalid = validation.invalidIndices.has(idx);
                    return `
                        <div class="time-slot-row ${isInvalid ? 'slot-invalid' : ''}" data-day="${day}" data-idx="${idx}">
                            <select class="time-select slot-start-select" data-day="${day}" data-idx="${idx}" aria-label="${day} slot ${idx + 1} start time">
                                ${buildOptionsHtml(slot.start_time)}
                            </select>
                            <span class="time-separator">to</span>
                            <select class="time-select slot-end-select" data-day="${day}" data-idx="${idx}" aria-label="${day} slot ${idx + 1} end time">
                                ${buildOptionsHtml(slot.end_time)}
                            </select>
                            <button type="button" class="btn-slot-remove" data-action="remove-slot" data-day="${day}" data-idx="${idx}" title="Remove time slot" aria-label="Remove ${day} slot ${idx + 1}">&times;</button>
                        </div>
                    `;
                }).join('');

                rightHtml = `
                    <div class="day-slots-list">${slotsHtml}</div>
                    <div class="day-row-actions">
                        <button type="button" class="btn-add-slot" data-action="add-slot" data-day="${day}">+ Add Time Slot</button>
                    </div>
                    ${validation.message ? `<div class="day-error-msg">${window.escapeHtml(validation.message)}</div>` : ''}
                `;
            }

            return `
                <div class="${rowClasses}" data-day="${day}">
                    <div class="day-row-left">
                        <label class="day-switch" aria-label="Toggle ${day} availability">
                            <input type="checkbox" class="day-toggle-checkbox" data-day="${day}" ${isEnabled ? 'checked' : ''}>
                            <span class="day-switch-slider"></span>
                        </label>
                        <div class="day-name-group">
                            <span class="day-name-label">${day}</span>
                            <span class="day-state-text">${stateText}</span>
                        </div>
                    </div>
                    <div class="day-row-right">
                        ${rightHtml}
                    </div>
                </div>
            `;
        }).join('');

        updateSummary();
    }

    function nextSuggestedSlot(existingSlots) {
        if (!existingSlots || existingSlots.length === 0) {
            return { start_time: '18:00', end_time: '21:00' };
        }
        // Suggest next free block after the latest end_time
        let maxEnd = 0;
        existingSlots.forEach(s => {
            const e = timeToMinutes(s.end_time);
            if (e !== null && e > maxEnd) maxEnd = e;
        });

        if (maxEnd <= 1260) { // <= 9:00 PM
            const nextStart = Math.min(maxEnd + 60, 1320);
            const nextEnd = Math.min(nextStart + 120, 1439);
            return {
                start_time: minutesToHHMM(nextStart),
                end_time: minutesToHHMM(nextEnd)
            };
        }
        if (maxEnd < 1410) {
            return {
                start_time: minutesToHHMM(maxEnd),
                end_time: '23:59'
            };
        }
        // Morning fallback if evening is full
        return { start_time: '09:00', end_time: '11:00' };
    }

    function clearFeedback() {
        if (!feedbackBanner) return;
        feedbackBanner.classList.add('hidden');
        feedbackBanner.classList.remove('is-success', 'is-error');
        feedbackBanner.textContent = '';
    }

    function showFeedback(message, type) {
        if (!feedbackBanner) return;
        feedbackBanner.textContent = message;
        feedbackBanner.className = `avail-feedback-banner ${type === 'success' ? 'is-success' : 'is-error'}`;
    }

    // Event Delegation on Status Options
    document.querySelectorAll('.status-option-card').forEach(card => {
        card.addEventListener('click', () => {
            const val = card.dataset.status;
            if (val && STATUS_LABELS[val]) {
                state.status = val;
                clearFeedback();
                syncStatusUI();
            }
        });
    });

    // Event Delegation on Weekly List
    if (weeklyListEl) {
        weeklyListEl.addEventListener('change', (e) => {
            const target = e.target;
            clearFeedback();

            if (target.classList.contains('day-toggle-checkbox')) {
                const day = target.dataset.day;
                if (target.checked) {
                    state.days[day].enabled = true;
                    if (state.days[day].slots.length === 0) {
                        state.days[day].slots.push(nextSuggestedSlot([]));
                    }
                } else {
                    state.days[day].enabled = false;
                    state.days[day].slots = [];
                }
                renderWeeklySchedule();
            } else if (target.classList.contains('slot-start-select')) {
                const day = target.dataset.day;
                const idx = parseInt(target.dataset.idx, 10);
                if (state.days[day] && state.days[day].slots[idx]) {
                    state.days[day].slots[idx].start_time = target.value;
                    renderWeeklySchedule();
                }
            } else if (target.classList.contains('slot-end-select')) {
                const day = target.dataset.day;
                const idx = parseInt(target.dataset.idx, 10);
                if (state.days[day] && state.days[day].slots[idx]) {
                    state.days[day].slots[idx].end_time = target.value;
                    renderWeeklySchedule();
                }
            }
        });

        weeklyListEl.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            clearFeedback();

            const action = btn.dataset.action;
            const day = btn.dataset.day;

            if (action === 'enable-add' || action === 'add-slot') {
                if (state.days[day].slots.length >= 10) {
                    window.showToast('Maximum 10 time slots allowed per day.', 'warning');
                    return;
                }
                state.days[day].enabled = true;
                state.days[day].slots.push(nextSuggestedSlot(state.days[day].slots));
                renderWeeklySchedule();
            } else if (action === 'remove-slot') {
                const idx = parseInt(btn.dataset.idx, 10);
                state.days[day].slots.splice(idx, 1);
                if (state.days[day].slots.length === 0) {
                    state.days[day].enabled = false;
                }
                renderWeeklySchedule();
            }
        });
    }

    // Quick Tools
    if (copyWeekdaysBtn) {
        copyWeekdaysBtn.addEventListener('click', () => {
            clearFeedback();
            const mon = state.days.Monday;
            if (!mon || !mon.enabled || mon.slots.length === 0) {
                window.showToast('Enable and configure Monday first before copying.', 'info');
                return;
            }
            const monCheck = validateDaySlots('Monday');
            if (!monCheck.valid) {
                window.showToast('Fix Monday schedule errors before copying.', 'error');
                return;
            }

            ['Tuesday', 'Wednesday', 'Thursday', 'Friday'].forEach(day => {
                state.days[day] = {
                    enabled: true,
                    slots: mon.slots.map(s => ({ ...s }))
                };
            });
            renderWeeklySchedule();
            window.showToast('Copied Monday hours to Tuesday–Friday. Click Save Availability to apply.', 'info');
        });
    }

    if (clearWeekBtn) {
        clearWeekBtn.addEventListener('click', () => {
            clearFeedback();
            DAYS.forEach(day => {
                state.days[day] = { enabled: false, slots: [] };
            });
            renderWeeklySchedule();
        });
    }

    // Save Handler
    async function handleSave(e) {
        if (e) e.preventDefault();
        clearFeedback();

        // Client-side pre-validation across all days
        const invalidDays = [];
        DAYS.forEach(day => {
            const v = validateDaySlots(day);
            if (!v.valid) {
                invalidDays.push({ day, message: v.message });
            }
        });

        if (invalidDays.length > 0) {
            renderWeeklySchedule();
            const first = invalidDays[0];
            showFeedback(`${first.day}: ${first.message}`, 'error');
            window.showToast(`${first.day}: ${first.message}`, 'error');
            return;
        }

        const btns = [saveTopBtn, saveBottomBtn].filter(Boolean);
        btns.forEach(b => {
            b.disabled = true;
            b.innerHTML = '<span class="spinner"></span> Saving...';
        });

        const res = await window.apiFetch('api/availability/save.php', {
            method: 'POST',
            body: {
                availability_status: state.status,
                schedule: state.days
            }
        });

        btns.forEach(b => {
            b.disabled = false;
            b.textContent = 'Save Availability';
        });

        if (res && res.success) {
            if (res.data && res.data.availability) {
                hydrateFromPayload(res.data.availability);
            }
            showFeedback('Availability schedule saved successfully.', 'success');
            window.showToast(res.message || 'Availability saved!', 'success');
        } else {
            const errMsg = (res && res.message) || 'Could not save availability. Please check your time slots.';
            showFeedback(errMsg, 'error');
            window.showToast(errMsg, 'error');
        }
    }

    if (form) {
        form.addEventListener('submit', handleSave);
    }
    if (saveTopBtn) {
        saveTopBtn.addEventListener('click', handleSave);
    }

    // Initialize from server-rendered JSON & refresh cleanly via GET API
    const initScript = document.getElementById('initial-availability-data');
    if (initScript) {
        try {
            const initialData = JSON.parse(initScript.textContent);
            hydrateFromPayload(initialData);
        } catch (err) {
            console.error('[Availability Init Parse Error]', err);
            renderWeeklySchedule();
        }
    } else {
        renderWeeklySchedule();
    }
});
