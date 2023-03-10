<div class="w2ui-page page-0">
    <div style="padding: 3px; font-weight: bold; color: #777;">Personal Information</div>
    <div class="w3-row">
        <div class="w3-third w3-container">
            <div class="w2ui-group" style="height: 185px;">
                <div class="w2ui-field">
                    <label>Family Name:</label>
                    <div>
                        <input name="last_name" type="text" id="last_name" maxlength="100" style="width: 100%">
                    </div>
                </div>
                <div class="w2ui-field">
                    <label>Given Name:</label>
                    <div>
                        <input name="first_name" type="text" id="first_name" maxlength="100" style="width: 100%">
                    </div>
                </div>
                <div class="w2ui-field">
                    <label>Middle Name:</label>
                    <div>
                        <input name="middle_name" type="text" id="middle_name" maxlength="100" style="width: 100%">
                    </div>
                </div>
            </div>
        </div>
        <div class="w3-third w3-container">
            <div class="w2ui-group" style="height: 185px;">
                <div class="w2ui-field">
                    <label>Birthday:</label>
                    <div>
                        <input name="bday" id="bday"/>
                    </div>
                </div>
                <div class="w2ui-field">
                    <label>Gender:</label>
                    <div>
                        <input name="gender" type="list" maxlength="100" id="gender" style="width: 50%">
                    </div>
                </div>
                <div class="w2ui-field">
                    <label>Civil Status:</label>
                    <div>
                        <input name="cs" type="list" maxlength="100" id="cs" style="width: 50%">
                    </div>
                </div>
                <div class="w2ui-field">
                    <label>Position:</label>
                    <div>
                        <input name="position" type="list" maxlength="100" id="position" style="width: 50%">
                    </div>
                </div>
                <div class="w2ui-field">
                    <label>Employment Date:</label>
                    <div>
                        <input name="edate" id="edate"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="w3-third w3-container">
            <div class="w3-center">
                <img id="pictid" src="images/no_profile_pic.gif" style="width:140px; height:140px; cursor:pointer; padding:4px;" class="w3-border" onclick="change_image()" />
                <form id="profileImg" onsubmit="return false">
                    <input type="hidden" name="emp_profile" type="text" id="emp_no1" readonly>
                    <input type="file" id="imgupload" accept="image/*" name="profile" style="display:none" onchange="document.getElementById('pictid').src = window.URL.createObjectURL(this.files[0])"/>
                    <input type="button" class="w3-button w3-orange w3-tiny w3-hide w3-left" value="UPLOAD PROFILE" id="upload_btn" onclick="upload_profile()">
                </form>
            </div>
        </div>
    </div>
    <div style="clear: both; padding-top: 15px;">
        <div style="padding: 3px; font-weight: bold; color: #777;">Additional Information</div>
        <div class="w3-row">
            <div class="w3-half w3-container">
                <div class="w2ui-group">
                    <div class="w2ui-field">
                        <label>Current Address:</label>
                        <div>
                            <textarea name="c_address" type="text" id="c_address" style="width: 100%; height: 80px; resize: none"></textarea>
                        </div>
                    </div>
                    <div class="w2ui-field">
                        <label>Permanent Address</label>
                        <div>
                            <textarea name="p_address" type="text" id="p_address" style="width: 100%; height: 80px; resize: none"></textarea>
                        </div>
                    </div>
                    <div class="w2ui-field">
                        <label>Contact No:</label>
                        <div>
                            <input name="contact" type="text" id="contact" maxlength="100" style="width: 100%">
                        </div>
                    </div>
                </div>
            </div>
            <div class="w3-half w3-container">
                <div class="w2ui-group">
                    <div class="w2ui-field">
                        <label>STORE:</label>
                        <div>
                            <input name="store" type="list" id="store" maxlength="100" style="width: 50%">
                        </div>
                    </div>
                    <div class="w2ui-field">
                        <label>EMPLOYEE NO:</label>
                        <div>
                            <input name="emp_no" type="text" id="emp_no" maxlength="100" style="width: 50%" readonly>
                        </div>
                    </div>
                    <div class="w2ui-field">
                        <label>ATM NO:</label>
                        <div>
                            <input name="atm" type="text" id="atm" maxlength="100" style="width: 50%">
                        </div>
                    </div>
                    <div class="w2ui-field">
                        <label>TIN NO:</label>
                        <div>
                            <input name="tin_compute" type="checkbox" id="tin_compute" value="1" style="width: auto;" >
                            <input name="tin" type="text" id="tin" maxlength="100" style="width:47.5%">
                        </div>
                    </div>
                    <div class="w2ui-field">
                        <label>SSS:</label>
                        <div>
                            <input name="sss_compute" type="checkbox" id="sss_compute" value="1" style="width: auto;" >
                            <input name="sss" type="text" id="sss" maxlength="100" style="width: 47.5%">
                        </div>
                    </div>
                    <div class="w2ui-field">
                        <label>PAG-IBIG:</label>
                        <div>
                            <input name="love_compute" type="checkbox" id="love_compute" value="1" style="width: auto;" >
                            <input name="love_prem" type="text" id="love_prem" maxlength="100" style="width: 9.5%" readonly>
                            <input name="love" type="text" id="love" maxlength="100" style="width: 37.5%">
                        </div>
                    </div>
                    <div class="w2ui-field">
                        <label>PHILHEALTH:</label>
                        <div>
                            <input name="ph_compute" type="checkbox" id="ph_compute" value="1" style="width: auto;" >
                            <input name="phealth" type="text" id="phealth" maxlength="100" style="width: 47.5%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div style="clear: both; padding-top: 15px;">
        <div class="w3-container">
            <div class="w2ui-group">
                <div class="w2ui-field">
                    <label>Remarks:</label>
                    <div>
                        <textarea name="remarks" id="remarks" type="text" style="width: 100%; height: 80px; resize: none"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>