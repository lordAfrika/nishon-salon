<?php
// client.php - database helper and booking/user functions
// Configure DB connection here
function db_connect(){
    static $pdo = null;
    if($pdo) return $pdo;
    $host = '127.0.0.1';
    $db   = 'nishon';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try{
        $pdo = new PDO($dsn, $user, $pass, $opts);
        return $pdo;
    }catch(PDOException $e){
        // In production, don't echo errors
        die('DB connection failed: ' . $e->getMessage());
    }
}

function get_user_by_email($email){
    $pdo = db_connect();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function get_user_by_id($id){
    $pdo = db_connect();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function register_user($full_name, $email, $phone, $password, $is_admin = 0){
    try{
        if(get_user_by_email($email)) return ['success'=>false, 'error'=>'Email already registered'];
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo = db_connect();
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password_hash, is_admin) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$full_name, $email, $phone, $hash, $is_admin]);
        return ['success'=>true, 'user_id'=>$pdo->lastInsertId(), 'is_admin'=>$is_admin];
    }catch(Exception $e){
        return ['success'=>false, 'error'=>'Registration failed: '.$e->getMessage()];
    }
}

function verify_login($email, $password){
    $user = get_user_by_email($email);
    if(!$user) return ['success'=>false, 'error'=>'User not found'];
    if(!password_verify($password, $user['password_hash'])) return ['success'=>false, 'error'=>'Invalid password'];
    return ['success'=>true, 'user_id'=>$user['id'], 'is_admin'=> (int)$user['is_admin']];
}

// Booking business rules
function booking_hour_count($date, $time, $exclude_booking_id = null){
    $pdo = db_connect();
    // Count bookings that share same date and hour
    $sql = 'SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND HOUR(booking_time) = HOUR(?)';
    $params = [$date, $time];
    if($exclude_booking_id){
        $sql .= ' AND id != ?'; $params[] = $exclude_booking_id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function is_within_working_hours($time){
    // expects HH:MM:SS
    return ($time >= '09:00:00' && $time <= '23:00:00');
}

function create_booking($user_id, $full_name, $phone, $booking_date, $booking_time, $message){
    try{
        // validate time
        if(!is_within_working_hours($booking_time)) return ['success'=>false,'error'=>'Booking time outside working hours (09:00-23:00)'];
        // check count
        $count = booking_hour_count($booking_date, $booking_time);
        if($count >= 5) return ['success'=>false,'error'=>'This hour is full — please pick another time'];
        $pdo = db_connect();
        $stmt = $pdo->prepare('INSERT INTO bookings (user_id, full_name, phone, booking_date, booking_time, message) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $full_name, $phone, $booking_date, $booking_time, $message]);
        return ['success'=>true, 'booking_id'=>$pdo->lastInsertId()];
    }catch(Exception $e){
        return ['success'=>false, 'error'=>'Failed to create booking: '.$e->getMessage()];
    }
}

function get_booking_by_id($id){
    $pdo = db_connect();
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function update_booking($booking_id, $full_name, $phone, $booking_date, $booking_time, $message, $current_user_id = null){
    try{
        $b = get_booking_by_id($booking_id);
        if(!$b) return ['success'=>false,'error'=>'Booking not found'];
        // if user id provided, ensure ownership unless admin
        if($current_user_id && $b['user_id'] != $current_user_id){
            // allow admins: check current user
            $u = get_user_by_id($current_user_id);
            if(!$u || !$u['is_admin']) return ['success'=>false,'error'=>'Not permitted to edit this booking'];
        }
        if(!is_within_working_hours($booking_time)) return ['success'=>false,'error'=>'Booking time outside working hours (09:00-23:00)'];
        $count = booking_hour_count($booking_date, $booking_time, $booking_id);
        if($count >= 5) return ['success'=>false,'error'=>'This hour is full — please pick another time'];
        $pdo = db_connect();
        $stmt = $pdo->prepare('UPDATE bookings SET full_name = ?, phone = ?, booking_date = ?, booking_time = ?, message = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$full_name, $phone, $booking_date, $booking_time, $message, $booking_id]);
        return ['success'=>true];
    }catch(Exception $e){
        return ['success'=>false,'error'=>'Failed to update booking: '.$e->getMessage()];
    }
}

function get_user_bookings($user_id){
    $pdo = db_connect();
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_date, booking_time');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_all_bookings(){
    $pdo = db_connect();
    $stmt = $pdo->query('SELECT b.*, u.email as user_email FROM bookings b LEFT JOIN users u ON b.user_id = u.id ORDER BY booking_date, booking_time');
    return $stmt->fetchAll();
}

function get_all_users(){
    $pdo = db_connect();
    $stmt = $pdo->query('SELECT id, full_name, email, phone, is_admin, created_at FROM users ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function delete_booking($id){
    $pdo = db_connect();
    $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
    return $stmt->execute([$id]);
}

function delete_user($id){
    $pdo = db_connect();
    // keep bookings but set user_id to NULL
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE bookings SET user_id = NULL WHERE user_id = ?');
    $stmt->execute([$id]);
    $stmt2 = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt2->execute([$id]);
    $pdo->commit();
    return true;
}

?>
