<?php
$section = $_POST['section'] ?? ($_GET['section'] ?? '');
$errors = [];
$messages = [];
$student = [
    'first_name' => '',
    'last_name' => '',
    'birthdate' => '',
    'gender' => '',
    'course' => '',
    'year_level' => '',
    'email' => '',
    'contact' => '',
    'address' => '',
    'guardian' => '',
    'guardian_contact' => '',
];
$enrollment_confirm = false;
$arithmetic = [];
$grade_result = '';
$text_analysis = '';
$username_check = '';
$strpos_result = '';
$time_result = '';
$search_terms = [];
$upload_info = '';
$image_preview = '';
$selected_link = '';

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($section) {
        case 'enrollment':
            foreach ($student as $field => $value) {
                $student[$field] = trim($_POST[$field] ?? '');
            }
            if ($student['first_name'] === '') { $errors[] = 'First name is required.'; }
            if ($student['last_name'] === '') { $errors[] = 'Last name is required.'; }
            if ($student['birthdate'] === '') { $errors[] = 'Birth date is required.'; }
            if ($student['gender'] === '') { $errors[] = 'Please select a gender.'; }
            if ($student['course'] === '') { $errors[] = 'Please choose a course.'; }
            if ($student['year_level'] === '') { $errors[] = 'Please choose a year level.'; }
            if ($student['email'] === '') { $errors[] = 'Email address is required.'; }
            elseif (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
            if ($student['contact'] === '') { $errors[] = 'Contact number is required.'; }
            if ($student['address'] === '') { $errors[] = 'Address is required.'; }
            if ($student['guardian'] === '') { $errors[] = 'Guardian name is required.'; }
            if ($student['guardian_contact'] === '') { $errors[] = 'Guardian contact number is required.'; }
            if (empty($errors)) { $enrollment_confirm = true; }
            break;

        case 'arithmetic':
            $n1 = $_POST['num1'] ?? '';
            $n2 = $_POST['num2'] ?? '';
            if ($n1 === '' || $n2 === '') { $errors[] = 'Both numbers are required.'; }
            else {
                $a = (float)$n1;
                $b = (float)$n2;
                $arithmetic = [
                    'addition' => $a + $b,
                    'subtraction' => $a - $b,
                    'multiplication' => $a * $b,
                    'division' => $b != 0 ? $a / $b : 'Cannot divide by zero',
                    'modulus' => $b != 0 ? $a % $b : 'Cannot modulus by zero',
                ];
            }
            break;

        case 'grade':
            $grade = $_POST['grade'] ?? '';
            if ($grade === '') { $errors[] = 'Please enter a grade.'; }
            else {
                $g = (int)$grade;
                if ($g >= 90) $grade_result = 'Grade A - Excellent!';
                elseif ($g >= 80) $grade_result = 'Grade B - Good job.';
                elseif ($g >= 70) $grade_result = 'Grade C - Fair.';
                elseif ($g >= 60) $grade_result = 'Grade D - Needs improvement.';
                else $grade_result = 'Grade F - Please try again.';
            }
            break;

        case 'validation':
            $name = trim($_POST['name'] ?? '');
            if ($name === '') { $errors[] = 'Name is required.'; }
            elseif (!preg_match('/^[a-zA-Z ]*$/', $name)) { $errors[] = 'Invalid name! Only letters and spaces are allowed.'; }
            else { $messages[] = 'Valid input: ' . safe($name); }
            break;

        case 'text':
            $text = trim($_POST['text'] ?? '');
            if ($text === '') { $errors[] = 'Enter some text to analyze.'; }
            else { $text_analysis = 'Length: ' . strlen($text) . ', Word count: ' . str_word_count($text); }
            break;

        case 'strcmp':
            $username = trim($_POST['username'] ?? '');
            if ($username === '') { $errors[] = 'Username is required!'; }
            else { $username_check = strcmp($username, 'admin') === 0 ? 'Valid username!' : 'Invalid username!'; }
            break;

        case 'strpos':
            $text = trim($_POST['text'] ?? '');
            $word = trim($_POST['word'] ?? '');
            if ($text === '' || $word === '') { $errors[] = 'Enter both text and a word to search.'; }
            else {
                $pos = strpos($text, $word);
                $strpos_result = $pos !== false ? 'Word found at position: ' . $pos : 'Word not found';
            }
            break;

        case 'time':
            $input = trim($_POST['date'] ?? '');
            if ($input === '') { $errors[] = 'Please enter a date string.'; }
            else {
                $timestamp = strtotime($input);
                if ($timestamp !== false) { $time_result = 'Timestamp: ' . $timestamp . ' | Formatted: ' . date('l, F d, Y', $timestamp); }
                else { $errors[] = 'Invalid time input!'; }
            }
            break;

        case 'parse':
            $search_input = trim($_POST['search'] ?? '');
            if ($search_input === '') { $errors[] = 'Please enter at least one search term.'; }
            else { $search_terms = array_map('trim', str_getcsv($search_input)); }
            break;

        case 'upload':
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $upload_info = 'Uploaded file: ' . safe($file['name']) . ', Type: ' . safe($file['type']) . ', Size: ' . number_format($file['size']) . ' bytes';
                $data = base64_encode(file_get_contents($file['tmp_name']));
                $mime = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
                $image_preview = '<img src="data:' . safe($mime) . ';base64,' . $data . '" style="max-width:100%; margin-top:10px;">';
            } else { $errors[] = 'Please select an image to upload.'; }
            break;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SISTIM
    </title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef2f7; margin: 0; }
        header { background: #1f3a93; color: white; padding: 22px 20px; text-align: center; }
        .content { max-width: 980px; margin: 24px auto; padding: 0 20px 40px; }
        .card { background: white; border-radius: 10px; box-shadow: 0 3px 12px rgba(0,0,0,.08); padding: 24px; margin-bottom: 20px; }
        .grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        input[type="text"], input[type="email"], input[type="date"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ccd0d9; border-radius: 6px; box-sizing: border-box; }
        textarea { min-height: 100px; resize: vertical; }
        input[type="submit"] { background: #1f3a93; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; }
        .message { padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .error { background: #fdecea; border: 1px solid #f5c6cb; color: #842029; }
        .success { background: #e9f7ef; border: 1px solid #c3e6cb; color: #0f5132; }
        dt { font-weight: 700; margin-top: 12px; }
        dd { margin: 0 0 12px 0; }
        .small-note { color: #6c7a89; font-size: 14px; margin-top: 10px; }

    </style>
</head>
<body>
    <header>
        <h1>SIGNAL SISTIM</h1>

    </header>
    <div class="content">
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <strong>Please fix these errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo safe($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>



        <div class="card" id="enrollment">
            <h2>Enrollment Form</h2>
            <form method="post">
                <input type="hidden" name="section" value="enrollment">
                <div class="grid">
                    <div><label>First Name</label><input name="first_name" type="text" value="<?php echo safe($student['first_name']); ?>"></div>
                    <div><label>Last Name</label><input name="last_name" type="text" value="<?php echo safe($student['last_name']); ?>"></div>
                    <div><label>Birth Date</label><input name="birthdate" type="date" value="<?php echo safe($student['birthdate']); ?>"></div>
                    <div><label>Gender</label><select name="gender"><option value="">Select gender</option><option value="Male" <?php echo $student['gender']==='Male' ? 'selected' : ''; ?>>Male</option><option value="Female" <?php echo $student['gender']==='Female' ? 'selected' : ''; ?>>Female</option><option value="Other" <?php echo $student['gender']==='Other' ? 'selected' : ''; ?>>Other</option></select></div>
                    <div><label>Course</label><select name="course"><option value="">Select a course</option><option value="BSIT" <?php echo $student['course']==='BSIT' ? 'selected' : ''; ?>>BSIT</option><option value="BSCS" <?php echo $student['course']==='BSCS' ? 'selected' : ''; ?>>BSCS</option><option value="BSEd" <?php echo $student['course']==='BSEd' ? 'selected' : ''; ?>>BSEd</option><option value="BSA" <?php echo $student['course']==='BSA' ? 'selected' : ''; ?>>BSA</option></select></div>
                    <div><label>Year Level</label><select name="year_level"><option value="">Select year level</option><option value="1st Year" <?php echo $student['year_level']==='1st Year' ? 'selected' : ''; ?>>1st Year</option><option value="2nd Year" <?php echo $student['year_level']==='2nd Year' ? 'selected' : ''; ?>>2nd Year</option><option value="3rd Year" <?php echo $student['year_level']==='3rd Year' ? 'selected' : ''; ?>>3rd Year</option><option value="4th Year" <?php echo $student['year_level']==='4th Year' ? 'selected' : ''; ?>>4th Year</option></select></div>
                    <div><label>Email</label><input name="email" type="email" value="<?php echo safe($student['email']); ?>"></div>
                    <div><label>Contact Number</label><input name="contact" type="text" value="<?php echo safe($student['contact']); ?>"></div>
                    <div style="grid-column: span 2;"><label>Address</label><textarea name="address"><?php echo safe($student['address']); ?></textarea></div>
                    <div><label>Guardian Name</label><input name="guardian" type="text" value="<?php echo safe($student['guardian']); ?>"></div>
                    <div><label>Guardian Contact</label><input name="guardian_contact" type="text" value="<?php echo safe($student['guardian_contact']); ?>"></div>
                </div>
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Submit Enrollment"></div>
            </form>

            <?php if ($enrollment_confirm): ?>
                <div class="message success" style="margin-top:20px;">
                    <h3>Enrollment Submitted</h3>
                    <p>The student enrollment information has been recorded.</p>
                    <dl>
                        <dt>Name</dt><dd><?php echo safe($student['first_name'] . ' ' . $student['last_name']); ?></dd>
                        <dt>Birth Date</dt><dd><?php echo safe($student['birthdate']); ?></dd>
                        <dt>Gender</dt><dd><?php echo safe($student['gender']); ?></dd>
                        <dt>Course</dt><dd><?php echo safe($student['course']); ?></dd>
                        <dt>Year Level</dt><dd><?php echo safe($student['year_level']); ?></dd>
                        <dt>Email</dt><dd><?php echo safe($student['email']); ?></dd>
                        <dt>Contact</dt><dd><?php echo safe($student['contact']); ?></dd>
                        <dt>Address</dt><dd><?php echo safe($student['address']); ?></dd>
                        <dt>Guardian</dt><dd><?php echo safe($student['guardian']); ?></dd>
                        <dt>Guardian Contact</dt><dd><?php echo safe($student['guardian_contact']); ?></dd>
                    </dl>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" id="arithmetic">
            <h2>Arithmetic Calculator</h2>
            <form method="post">
                <input type="hidden" name="section" value="arithmetic">
                <div class="grid">
                    <div><label>Number 1</label><input name="num1" type="text"></div>
                    <div><label>Number 2</label><input name="num2" type="text"></div>
                </div>
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Calculate"></div>
            </form>
            <?php if (!empty($arithmetic)): ?>
                <div class="message success" style="margin-top:20px;">
                    <p>Addition: <?php echo safe($arithmetic['addition']); ?></p>
                    <p>Subtraction: <?php echo safe($arithmetic['subtraction']); ?></p>
                    <p>Multiplication: <?php echo safe($arithmetic['multiplication']); ?></p>
                    <p>Division: <?php echo safe($arithmetic['division']); ?></p>
                    <p>Modulus: <?php echo safe($arithmetic['modulus']); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" id="datatype">
            <h2>Data Type Checking</h2>
            <?php $a = true; $b = 10; $c = 3.14; $d = null; $e = '123'; ?>
            <p>a is bool: <?php echo is_bool($a) ? 'Tama' : 'Mali'; ?></p>
            <p>b is int: <?php echo is_int($b) ? 'Yes' : 'No'; ?></p>
            <p>c is float: <?php echo is_float($c) ? 'Yes' : 'No'; ?></p>
            <p>d is null: <?php echo is_null($d) ? 'Yes' : 'No'; ?></p>
            <p>e is numeric: <?php echo is_numeric($e) ? 'Yes' : 'No'; ?></p>
        </div>

        <div class="card" id="date">
            <h2>Date / Time</h2>
            <?php date_default_timezone_set('Asia/Manila'); ?>
            <p>Today is: <?php echo date('l, F d, Y'); ?></p>
            <p>Current time: <?php echo date('h:i:s A'); ?></p>
        </div>

        <div class="card" id="grade">
            <h2>Grade Checker</h2>
            <form method="post">
                <input type="hidden" name="section" value="grade">
                <label>Grade</label>
                <input name="grade" type="text">
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Check Grade"></div>
            </form>
            <?php if ($grade_result): ?><div class="message success" style="margin-top:20px;"><?php echo safe($grade_result); ?></div><?php endif; ?>
        </div>

        <div class="card" id="validation">
            <h2>Input Validation</h2>
            <form method="post">
                <input type="hidden" name="section" value="validation">
                <label>Name</label>
                <input name="name" type="text">
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Validate"></div>
            </form>
            <?php foreach ($messages as $message): ?><div class="message success" style="margin-top:20px;"><?php echo safe($message); ?></div><?php endforeach; ?>
        </div>

        <div class="card" id="parse">
            <h2>Parse Search Terms</h2>
            <form method="post">
                <input type="hidden" name="section" value="parse">
                <label>Search words (comma separated)</label>
                <input name="search" type="text">
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Parse"></div>
            </form>
            <?php if (!empty($search_terms)): ?><div class="message success" style="margin-top:20px;"><strong>Terms:</strong><ul><?php foreach ($search_terms as $term): ?><li><?php echo safe($term); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        </div>

        <div class="card" id="text">
            <h2>Text Analysis</h2>
            <form method="post">
                <input type="hidden" name="section" value="text">
                <label>Text</label>
                <textarea name="text"></textarea>
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Analyze"></div>
            </form>
            <?php if ($text_analysis): ?><div class="message success" style="margin-top:20px;"><?php echo safe($text_analysis); ?></div><?php endif; ?>
        </div>

        <div class="card" id="strcmp">
            <h2>Username Check</h2>
            <form method="post">
                <input type="hidden" name="section" value="strcmp">
                <label>Username</label>
                <input name="username" type="text">
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Check Username"></div>
            </form>
            <?php if ($username_check): ?><div class="message success" style="margin-top:20px;"><?php echo safe($username_check); ?></div><?php endif; ?>
        </div>

        <div class="card" id="strpos">
            <h2>String Search</h2>
            <form method="post">
                <input type="hidden" name="section" value="strpos">
                <label>Sentence</label>
                <input name="text" type="text">
                <label>Word</label>
                <input name="word" type="text">
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Search"></div>
            </form>
            <?php if ($strpos_result): ?><div class="message success" style="margin-top:20px;"><?php echo safe($strpos_result); ?></div><?php endif; ?>
        </div>

        <div class="card" id="time">
            <h2>Time Parser</h2>
            <form method="post">
                <input type="hidden" name="section" value="time">
                <label>Date string</label>
                <input name="date" type="text" placeholder="e.g. 2018-08-12">
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Parse Date"></div>
            </form>
            <?php if ($time_result): ?><div class="message success" style="margin-top:20px;"><?php echo safe($time_result); ?></div><?php endif; ?>
        </div>

        <div class="card" id="upload">
            <h2>Image Upload</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="section" value="upload">
                <label>Select image</label>
                <input name="image" type="file" accept="image/*">
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Upload Image"></div>
            </form>
            <?php if ($upload_info): ?><div class="message success" style="margin-top:20px;"><?php echo safe($upload_info); ?></div><?php echo $image_preview; endif; ?>
        </div>

        <div class="card" id="links">
            <h2>Link Test</h2>
            <form method="post">
                <input type="hidden" name="section" value="links">
                <label>Choose a page</label>
                <select name="selected_link">
                    <option value="">Select</option>
                    <option value="buy" <?php echo $selected_link === 'buy' ? 'selected' : ''; ?>>Buy products</option>
                    <option value="browse" <?php echo $selected_link === 'browse' ? 'selected' : ''; ?>>Browse products</option>
                    <option value="help" <?php echo $selected_link === 'help' ? 'selected' : ''; ?>>Need assistance</option>
                </select>
                <div style="text-align:right; margin-top:16px;"><input type="submit" value="Show"></div>
            </form>
            <?php if ($selected_link): ?><div class="message success" style="margin-top:20px;">You are in the <?php echo safe($selected_link); ?> section.</div><?php endif; ?>
        </div>

        <div class="card" id="loop">
            <h2>Loop Example</h2>
            <?php $group = [ 'John Paolo', 'Erich', 'Macaspac', 'John Paolo', 'Aala', 'Erich Macaspac', 'Jai Rus']; $count = 0; while ($count < 7) { echo '<p>Member: ' . safe($group[$count]) . '</p>'; $count++; } ?>
        </div>

        <div class="card" id="strings">
            <h2>String Example</h2>
            <p>John Paolo Aala is a student of BSIT in 2nd year.</p>
            <p>My name is Jai Rose. I am a student of BSIT in 2nd year.</p>
        </div>

    </div>
</body>
</html>
