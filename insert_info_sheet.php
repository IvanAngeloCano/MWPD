<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Add New Information Sheet Record";
include '_head.php';

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Calculate total PCT if time_received and time_released are provided
        $timeReceived = !empty($_POST['time_received']) ? $_POST['time_received'] : null;
        $timeReleased = !empty($_POST['time_released']) ? $_POST['time_released'] : null;
        $totalPct = null;
        
        if ($timeReceived && $timeReleased) {
            $start = new DateTime($timeReceived);
            $end = new DateTime($timeReleased);
            $diff = $start->diff($end);
            $totalPct = $diff->format('%H:%I:%S');
        }
        
        $sql = "INSERT INTO info_sheet (
            family_name, first_name, middle_name, gender, jobsite, name_of_agency,
            purpose, specify_if_others_purpose, worker_category, specify_if_regional_office_or_polo,
            requested_record, documents_presented, if_others_documents_presented,
            year_of_action_taken, ofw_record_released, number_of_records_retrieved_printed,
            time_received, time_released, total_pct, remarks_if_pct_not_met, specify_if_others_final
        ) VALUES (
            :family_name, :first_name, :middle_name, :gender, :jobsite, :name_of_agency,
            :purpose, :specify_if_others_purpose, :worker_category, :specify_if_regional_office_or_polo,
            :requested_record, :documents_presented, :if_others_documents_presented,
            :year_of_action_taken, :ofw_record_released, :number_of_records_retrieved_printed,
            :time_received, :time_released, :total_pct, :remarks_if_pct_not_met, :specify_if_others_final
        )";

        $params = $_POST;
        $params['total_pct'] = $totalPct;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $message = "Record inserted successfully!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Error inserting record: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>

<style>
    .form-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        animation: fadeIn 0.6s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .form-title {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
        color: #333;
        font-weight: 700;
    }
    
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f5f5f5;
    }
    
    .form-section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 25px;
        color: #4e73df;
        display: flex;
        align-items: center;
        position: relative;
    }
    
    .form-section-title i {
        margin-right: 15px;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        box-shadow: 0 4px 10px rgba(78, 115, 223, 0.3);
        transition: all 0.3s ease;
    }
    
    .form-section:hover .form-section-title i {
        transform: scale(1.1) rotate(5deg);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #555;
    }
    
    .form-control {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 12px 15px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        font-size: 14px;
    }
    
    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        transform: translateY(-2px);
    }
    
    .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -15px;
        margin-left: -15px;
        margin-bottom: 20px;
    }
    
    .col-md-4 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
        padding-right: 15px;
        padding-left: 15px;
        position: relative;
    }
    
    .col-md-6 {
        flex: 0 0 50%;
        max-width: 50%;
        padding-right: 15px;
        padding-left: 15px;
        position: relative;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(28, 200, 138, 0.2);
        margin-right: 15px;
    }
    
    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(28, 200, 138, 0.3);
    }
    
    .btn-back {
        background: linear-gradient(135deg, #858796 0%, #5a5c69 100%);
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(133, 135, 150, 0.2);
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-back:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(133, 135, 150, 0.3);
        color: white;
        text-decoration: none;
    }
    
    .form-actions {
        display: flex;
        margin-top: 30px;
    }
    
    .btn-cancel {
        background-color: #f8f9fc;
        color: #6c757d;
        border: 1px solid #ddd;
        padding: 12px 25px;
        border-radius: 5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-right: 10px;
    }
    
    .btn-cancel:hover {
        background-color: #eaecf4;
    }
    
    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background-color: rgba(28, 200, 138, 0.1);
        color: #1cc88a;
        border: 1px solid rgba(28, 200, 138, 0.2);
    }
    
    .alert-danger {
        background-color: rgba(231, 74, 59, 0.1);
        color: #e74a3b;
        border: 1px solid rgba(231, 74, 59, 0.2);
    }
    
    .conditional-field {
        display: none;
    }
</style>

<div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include '_header.php'; ?>
        
        <main class="main-content">
            <div class="form-container">
                <?php if(!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <h1 class="form-title">Add New Information Sheet Record</h1>
                
                <form method="POST" action="">
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i class="fas fa-user"></i> Personal Information
                        </h2>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Family Name</label>
                                    <input type="text" name="family_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Jobsite</label>
                                    <input type="text" name="jobsite" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Name of Agency</label>
                                    <input type="text" name="name_of_agency" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Purpose and Category Section -->
                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i class="fas fa-tasks"></i> Purpose and Category
                        </h2>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Purpose</label>
                                    <select name="purpose" id="purpose" class="form-control" required>
                                        <option value="">Select Purpose</option>
                                        <option value="EMPLOYMENT">EMPLOYMENT</option>
                                        <option value="OWWA">OWWA</option>
                                        <option value="LEGAL">LEGAL</option>
                                        <option value="LOAN">LOAN</option>
                                        <option value="VISA">VISA</option>
                                        <option value="BM">BM</option>
                                        <option value="RTT">RTT</option>
                                        <option value="PHILHEALTH">PHILHEALTH</option>
                                        <option value="OTHERS">OTHERS</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group conditional-field" id="others_purpose_field">
                                    <label class="form-label">Specify Other Purpose</label>
                                    <input type="text" name="specify_if_others_purpose" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Worker Category</label>
                                    <select name="worker_category" class="form-control" required>
                                        <option value="">Select Worker Category</option>
                                        <option value="LAND BASED">LAND BASED</option>
                                        <option value="REHIRE">REHIRE</option>
                                        <option value="SEAFARER">SEAFARER</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Regional Office/POLO</label>
                                    <input type="text" name="specify_if_regional_office_or_polo" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Requested Records Section -->
                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i class="fas fa-file-alt"></i> Requested Records
                        </h2>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Requested Record</label>
                                    <select name="requested_record" class="form-control" required>
                                        <option value="">Select Requested Record</option>
                                        <option value="INFO SHEET">INFO SHEET</option>
                                        <option value="OEC">OEC</option>
                                        <option value="CONTRACT">CONTRACT</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Documents Presented</label>
                                    <select name="documents_presented" id="documents_presented" class="form-control">
                                        <option value="">Select Documents Presented</option>
                                        <option value="PASSPORT">PASSPORT</option>
                                        <option value="VALID ID">VALID ID</option>
                                        <option value="OTHERS">OTHERS</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group conditional-field" id="others_documents_field">
                                    <label class="form-label">Specify Other Documents</label>
                                    <input type="text" name="if_others_documents_presented" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Year of Action Taken</label>
                                    <input type="number" name="year_of_action_taken" class="form-control" min="1900" max="2099">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Processing Details Section -->
                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i class="fas fa-clock"></i> Processing Details
                        </h2>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">OFW Record Released Date</label>
                                    <input type="date" name="ofw_record_released" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Number of Records Retrieved/Printed</label>
                                    <input type="number" name="number_of_records_retrieved_printed" class="form-control" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Remarks (if PCT not met)</label>
                                    <input type="text" name="remarks_if_pct_not_met" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Time Received</label>
                                    <input type="time" name="time_received" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Time Released</label>
                                    <input type="time" name="time_released" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Other Specifications</label>
                                    <input type="text" name="specify_if_others_final" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="information_sheet.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Record</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script>
    // Show/hide conditional fields based on selections
    document.addEventListener('DOMContentLoaded', function() {
        const purposeSelect = document.getElementById('purpose');
        const othersField = document.getElementById('others_purpose_field');
        
        purposeSelect.addEventListener('change', function() {
            if (this.value === 'OTHERS') {
                othersField.style.display = 'block';
            } else {
                othersField.style.display = 'none';
            }
        });
        
        const documentsSelect = document.getElementById('documents_presented');
        const othersDocField = document.getElementById('others_documents_field');
        
        documentsSelect.addEventListener('change', function() {
            if (this.value === 'OTHERS') {
                othersDocField.style.display = 'block';
            } else {
                othersDocField.style.display = 'none';
            }
        });
    });
</script>
