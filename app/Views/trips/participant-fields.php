<label>Name
    <input name="name" required autocomplete="name">
</label>
<label>Email
    <input type="email" name="email" required autocomplete="email">
</label>
<label>Phone
    <input name="phone" autocomplete="tel">
</label>
<label>Emergency Contact Name
    <input name="emergency_contact_name" required>
</label>
<label>Emergency Contact Phone
    <input name="emergency_contact_phone" required autocomplete="tel">
</label>
<label>Emergency Contact Relationship
    <input name="emergency_contact_relationship" placeholder="Spouse, parent, friend, etc.">
</label>
<label class="checkbox-row full-width minor-toggle-row">
    <input type="checkbox" name="is_minor" value="1" id="is_minor"> Participant is a minor
</label>
<div class="guardian-fields full-width" id="guardian-fields" hidden>
    <div class="form-grid guardian-grid">
        <label>Guardian Name
            <input name="guardian_name" id="guardian_name" autocomplete="name">
        </label>
        <label>Guardian Email
            <input type="email" name="guardian_email" id="guardian_email" autocomplete="email">
        </label>
    </div>
    <p class="field-help">A parent or legal guardian must complete the waiver signature for a minor participant.</p>
</div>
<label class="full-width">Medical / Safety Notes
    <textarea name="medical_notes" rows="4" placeholder="Optional: medical conditions, allergies, medications, physical limitations, or other information the trip leader should know for this trip or an emergency."></textarea>
    <span class="field-help">Optional. Restricted safety information for trip leadership and authorized emergency use.</span>
</label>
<script>
(function () {
    const minor = document.getElementById('is_minor');
    const guardian = document.getElementById('guardian-fields');
    const guardianName = document.getElementById('guardian_name');
    const guardianEmail = document.getElementById('guardian_email');
    if (!minor || !guardian) return;

    function syncGuardianFields() {
        const show = minor.checked;
        guardian.hidden = !show;
        guardianName.required = show;
        guardianEmail.required = show;
        if (!show) {
            guardianName.value = '';
            guardianEmail.value = '';
        }
    }

    minor.addEventListener('change', syncGuardianFields);
    syncGuardianFields();
})();
</script>
