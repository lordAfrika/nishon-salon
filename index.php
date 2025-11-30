<?php
require_once __DIR__ . '/client.php';
require_once __DIR__ . '/csrf.php';
// Start session only if not already active
if (function_exists('session_status')) {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }
} else {
  if (session_id() === '') {
    session_start();
  }
}
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/csrf.php';

// Verify CSRF token for all POST requests
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        flash('Security check failed. Please try again.');
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
}

// Simple flash helper
function flash($msg){
  $_SESSION['flash'] = $msg;
}

function get_flash(){
  if(!empty($_SESSION['flash'])){ $m = $_SESSION['flash']; unset($_SESSION['flash']); return $m; }
  return '';
}

// Handle registration
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $action = $_POST['action'] ?? '';
  if($action === 'register'){
    $full = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass = $_POST['password'] ?? '';
    if($full === '' || $email === '' || $pass === ''){
      flash('Please fill required fields for registration');
    } else {
      $res = register_user($full, $email, $phone, $pass);
      if($res['success']){
        $_SESSION['user_id'] = $res['user_id'];
        $_SESSION['is_admin'] = $res['is_admin'];
        flash('Registration successful. Logged in.');
      } else {
        flash($res['error']);
      }
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
  }

  if($action === 'login'){
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $res = verify_login($email, $pass);
    if($res['success']){
      $_SESSION['user_id'] = $res['user_id'];
      $_SESSION['is_admin'] = $res['is_admin'];
      flash('Login successful');
    } else {
      flash($res['error']);
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
  }

  if($action === 'logout'){
    session_unset(); session_destroy();
    session_start(); flash('Logged out');
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
  }

  if($action === 'book'){
    // booking form
    $full = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dt = $_POST['selectedDateTime'] ?? '';
    $message = trim($_POST['message'] ?? '');
    $user_id = $_SESSION['user_id'] ?? null;
    if($dt === '' || $full === '' ){ flash('Please fill name and date/time'); header('Location: ' . $_SERVER['PHP_SELF']); exit; }
    // datetime-local gives YYYY-MM-DDTHH:MM
    $parts = explode('T', $dt);
    if(count($parts) !== 2){ flash('Bad date/time format'); header('Location: ' . $_SERVER['PHP_SELF']); exit; }
    $date = $parts[0];
    $time = $parts[1] . ':00';
    $r = create_booking($user_id, $full, $phone, $date, $time, $message);
    if($r['success']) flash('Booking created'); else flash($r['error']);
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
  }

  if($action === 'edit_booking'){
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $full = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dt = $_POST['selectedDateTime'] ?? '';
    $message = trim($_POST['message'] ?? '');
    if($booking_id <= 0){ flash('Invalid booking'); header('Location: ' . $_SERVER['PHP_SELF']); exit; }
    $parts = explode('T', $dt);
    if(count($parts) !== 2){ flash('Bad date/time format'); header('Location: ' . $_SERVER['PHP_SELF']); exit; }
    $date = $parts[0];
    $time = $parts[1] . ':00';
    $r = update_booking($booking_id, $full, $phone, $date, $time, $message, $_SESSION['user_id'] ?? null);
    if($r['success']) flash('Booking updated'); else flash($r['error']);
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
  }
}

?>
<!doctype html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="remixicons/fonts/remixicon.css">
  <link rel="stylesheet" href="css/style.css">

  <title>Nishon Salon</title>
</head>

<body id="home" data-bs-spy="scroll" data-bs-target=".navbar">

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg  navbar-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="#"> <span>N</span>ishon </a>

      <a href="#booking" class="btn btn-outline-brand">Book Now!</a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">Home</a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="#about">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#service">Services</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#features">Features</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#team">Team</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#reviews">Reviews</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#blog">Blog</a>
          </li>
          <?php if(!empty(
            
            
            
            $_SESSION['user_id'])): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo htmlspecialchars(get_user_by_id($_SESSION['user_id'])['full_name'] ?? 'User'); ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                <?php if(!empty($_SESSION['is_admin'])): ?>
                  <li><a class="dropdown-item" href="database.php">Admin panel</a></li>
                <?php endif; ?>
                <li>
                  <form method="post" style="margin:0">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="logout">
                    <button class="dropdown-item" type="submit">Logout</button>
                  </form>
                </li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="#booking">Login / Register</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- // NAVBAR -->


  <!-- HERO -->
  <section id="hero">

    <div class="container">
      <div class="row">
        <div class="col-8">
          <h1 class="display-1">
            Let your hair do the talking.
          </h1>
         
          <p id="small">WELCOME to    <span>N</span><span class="small">ishon</span> salon <br> We are customed to accomodate our customers as pleased.</p>


          <a href="#team" class="btn btn-brand">Get in touch</a>

        </div>
      </div>
    </div>
  </section>

  <!-- // HERO -->


  <!-- ABOUT -->
  <section id="about">
    <div class="container text-center ">
      <div class="row aling-items-center">
        <div class="col-lg-6">
          <img src="./img/about_img.png" width="200%">
        </div>
        <div class="col-lg-6 offset-lg-1 ">
          <h6 class="mt-5">About us</h6>
          <h1>About <span>N</span>ishon salon</h1>
          <p>
            Ther are many ways to be confident about your looks, we not just give you confidence 
            we make you superior!.
          </p>
          <p>
            We are different yes we are Yesss!.
          </p>
          <img class="signature" src="./img/signature.png" alt="">
        </div>
      </div>
    </div>
  </section>
  <!-- // ABOUT -->


  <!-- SERVICES -->
  <section id="service">
    <div class="container text-center">
      <div class="row">
        <div class="col-12 intro">
          <h6>Services</h6>
          <h1>What we provide?</h1>
          <p>We provide viraties of essential touch that makes our custumers always up to something sweet!</p>
        </div>
      </div>
      <div class="row gy-5">
        <div class="col-lg-4 col-md-4 col-sm-1 ">
          <div class="service">
            <img src="./img/service_1.jpg" alt="">
            <div class="content">
              <h3 class="h5">Clean cut</h3>
              <p>Proper and clean styling of your choice is our passion.
              </p>
              <a href="" class="link-more icon">Know more &rightarrow; </a>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-4 col-sm-1">
          <div class="service">
            <img src="./img/service_2.jpg" alt="">
            <div class="content">
              <h3 class="h5">Trimmig</h3>
              <p>No room for imperfection ,all shaves are our top piority especially all.
              </p>
              <a href="" class="link-more icon">Know more &rightarrow; </a>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-1">
          <div class="service">
            <img src="./img/service_3.jpg" alt="">
            <div class="content">
              <h3 class="h5">Visual style Trim</h3>
              <p>Making sure every cut shows.
              </p>
              <a href="" class="link-more icon">Know more &rightarrow; </a>
            </div>
          </div>
        </div>
      </div>
      <div class="cta-btns ">
        <a href="#booking" class="btn btn-brand me-sm-2 my-3">Appointment</a>
        <a href="#team" class="btn btn-outline-brand ms-sm-2 ">Get in touch</a>


      </div>
    </div>
  </section>
  <!-- // SERVICES -->


  <!-- MILESTONE -->
  <section id="milestone">
    <div class="container text-center">
      <div class="row">
        <div class="col-lg-3 col-sm-3 counter">
          <h1>5432 </h1>
          <p>For over 3 years</p>
        </div>

        <div class="col-lg-3 col-sm-3 counter">
          <h1>5432 </h1>
          <p>Everyday shades</p>
        </div>

        <div class="col-lg-3 col-sm-3 counter">
          <h1>5432 </h1>
          <p>Every customers</p>
        </div>

        <div class="col-lg-3 col-sm-3 counter">
          <h1>5432 </h1>
          <p>Are documented</p>
        </div>

      </div>
    </div>

  </section>

  <!-- // MILESTONE-->

  <!-- FEATURES -->
  <section id="features">
    <div class="container text-center">
      <div class="row">
        <div class="col-12 intro">
          <h6>Features </h6>
          <h1>Why we are awesome! </h1>
          <p>We are Absolutely conducive and accomadating also trustwhothy </p>
        </div>
      </div>
      <div class="row gy-5">
        <div class="col-lg-3 col-sm-2">
          <div class="feature">
            <div class="icon-feature"></div>
            <div>
              <h3 class="h5"> Security </h3>
              <p> A ranged number of professional security personel are always on standby
                and alerts.
              </p>
              

            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-2">
          <div class="feature">
            <div class="icon-feature"></div>
            <div>
              <h3 class="h5"> Internet </h3>
              <p>We provide free wifi for customers, sweet and fast.</p>
              

            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-2">
          <div class="feature">
            <div class="icon-feature"></div>
            <div>
              <h3 class="h5"> Errands </h3>
              <p>No need to step out,     you need to get something imediately?,we provide proficcient individuals
                for just that.
              </p>
              

            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-2">
          <div class="feature">
            <div class="icon-feature"></div>
            <div>
              <h3 class="h5"> Sounds and sight </h3>
              <p>Proper sound system and mirro staging, you cant step in unnoticed and cant wait bored.</p>
              

            </div>
          </div>
        </div>

    



      <div class="cta-btns ">
        <a href="#booking" class="btn btn-brand me-sm-2 my-3">Appointment</a>
        <a href="#team" class="btn btn-outline-brand ms-sm-2 ">Get in touch</a>


      </div>
    </div>
  </section>
  <!-- // FEATURES-->


  <!-- TEAM -->
  <section id="team">
    <div class="container text-center">
      <div class="row">
        <div class="col-12 intro">
          <h6>TEAM</h6>
          <h1>Meet our crew members</h1>
          <p>A crew of the most gentle  and exelent barbers you will witness.</p>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-4 col-md-4">
          <div class="team-members">
            <img src="./img/team_member1.jpg" alt="">
            <div class="social-links">
              <a href="" class="link-icon"><img src="./img/facebook.png"></a>
              <a href="" class="link-icon"><img src="./img/instagram.png"></a>
              <a href="" class="link-icon"><img src="./img/twitter.png"></a>
              <a href="" class="link-icon"><img src="./img/youtube.png"></a>

            </div>
            <h5 class="mt-2">Wade nilston</h5>
            <p>baber</p>
          </div>
        </div>

        <div class="col-lg-4 col-md-4">
          <div class="team-members">
            <img src="./img/team_member2.jpg" alt="">
            <div class="social-links">
              <a href="" class="link-icon"><img src="./img/facebook.png"></a>
              <a href="" class="link-icon"><img src="./img/instagram.png"></a>
              <a href="" class="link-icon"><img src="./img/twitter.png"></a>
              <a href="" class="link-icon"><img src="./img/youtube.png"></a>

            </div>
            <h5 class="mt-2">Curly west</h5>
            <p>Chief baber</p>
          </div>
        </div>

        <div class="col-lg-4 col-md-4">
          <div class="team-members">
            <img src="./img/team_member3.jpg" alt="">
            <div class="social-links">
              <a href="" class="link-icon"><img src="./img/facebook.png"></a>
              <a href="" class="link-icon"><img src="./img/instagram.png"></a>
              <a href="" class="link-icon"><img src="./img/twitter.png"></a>
              <a href="" class="link-icon"><img src="./img/youtube.png"></a>

            </div>
            <h5 class="mt-2">Vicent don</h5>
            <p>hair cutter</p>
          </div>
        </div>

      </div>

      <div class="cta-btns ">
        <a href="#booking" class="btn btn-brand me-sm-2 my-3">Appointment</a>
        


      </div>
    </div>
  </section>
  <!-- // TEAM -->


  <!-- TESTIMONIALS -->
  <section id="reviews">
    <div class="container text-center">
      <div class="row">
        <div class="col-12 intro">
          <h6>REVIEWS</h6>
          <h1>Listen to our customers</h1>
          <p>Our customers compliments are preciuos to us, we are glad to know how you feel</p>
        </div>
      </div>
      <div class="row gy-3">
        <div class="col-lg3 col-md-4">
          <div class="review">
            <div class="img d-flex"><img src="./img/avatar_01.jpg" alt="">
              <div class="ms-3 mb-4">
                <h5> will</h5>
                <small>@willnode</small>
              </div>
              <div class="icon"><img src="./img/twitter.png" id="img"></div>
            </div>
            <p class="mt-4">life is attributed to a quality hair cut from a good
              barber you will find both here.</p>
          </div>
        </div>

        <div class="col-lg3 col-md-4">
          <div class="review">
            <div class="img d-flex"><img src="./img/avatar_02.jpg" alt="">
              <div class="ms-3 mb-4">
                <h5> Gabriel</h5>
                <small>@gagaman</small>
              </div>
              <div class="icon"><img src="./img/instagram.png" id="img"></div>
            </div>
            <p class="mt-4">i can never replace Nishon!!!!.</p>
          </div>
        </div>
        <div class="col-lg3 col-md-4">
          <div class="review">
            <div class="img d-flex"><img src="./img/avatar_03.jpg" alt="">
              <div class="ms-3 mb-4">
                <h5> Max</h5>
                <small>@maxwell</small>
              </div>
              <div class="icon"><img src="./img/facebook.png" id="img"></div>
            </div>
            <p class="mt-4">I dont just go there for a good cut, but to feel rich.</p>
          </div>
        </div>
        <div class="col-lg3 col-md-4">
          <div class="review">
            <div class="img d-flex"><img src="./img/avatar_04.jpg" alt="">
              <div class="ms-3 mb-4">
                <h5> Abraham</h5>
                <small>@abthegreat</small>
              </div>
              <div class="icon"><img src="./img/twitter.png" id="img"></div>
            </div>
            <p class="mt-4">i enjoy every moment at nishon.</p>
          </div>
        </div>
        <div class="col-lg3 col-md-4">
          <div class="review">
            <div class="img d-flex"><img src="./img/avatar_05.jpg" alt="">
              <div class="ms-3 mb-4">
                <h5> ufedo</h5>
                <small>@fedonedo</small>
              </div>
              <div class="icon"><img src="./img/instagram.png" id="img"></div>
            </div>
            <p class="mt-4">The vibe from the customers and babers is just evrything.</p>
          </div>
        </div>
        <div class="col-lg3 col-md-4">
          <div class="review">
            <div class="img d-flex"><img src="./img/avatar_06.jpg" alt="">
              <div class="ms-3 mb-4">
                <h5> williams</h5>
                <small>@willams</small>
              </div>
              <div class="icon"><img src="./img/facebook.png" id="img"></div>
            </div>
            <p class="mt-4">cant explain how i got in but never left hahahah.</p>
          </div>
        </div>


      </div>


      <div class="cta-btns">
        <a href="#booking" class="btn btn-brand me-sm-2 my-3">Appointment</a>
        <a href="#team" class="btn btn-outline-brand ms-sm-2 ">Get in touch</a>


      </div>
    </div>
  </section>
  <!-- // TESTIMONIALS -->


  <!-- BLOG -->
  <section id="blog">
    <div class="container text-center">
      <div class="row">
        <div class="col-12 intro">
          <h6>Blog</h6>
          <h1>Latest IN Styles</h1>
          <p>Apperantly our goal is to keep our shades updated, with our unique touchs</p>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-md-6">
          <article class="blog-post">
            <img src="./img/1.jpg" alt="">
            <div class="date">
              <div>
                <div class="day">12</div>
                <div class="year">Nov, 2025</div>
              </div>
            </div>
            <h4 class=" mt-4">2025 Men's hair trends everwhere</h4>
            <p class="my-3"> Fill in your confidence with your crown <span>&star;</span></p>
          </article>
        </div>

        <div class="col-lg-6 col-md-6">
          <article class="blog-post">
            <img src="./img/2.jpg" alt="">
            <div class="date">
              <div>
                <div class="day">12</div>
                <div class="year">Nov, 2025</div>
              </div>
            </div>
            <h4 class=" mt-4">2025 Men's hair trends everwhere</h4>
            <p class="my-3"> Fill in your confidence with your crown <span>&star;</span></p>
          </article>
        </div>

      </div>


      <div class="cta-btns ">
        <a href="#booking" class="btn btn-brand my-3 me-sm-2">Appointment</a>
        <a href="#team" class="btn btn-outline-brand ms-sm-2 ">Get in touch</a>


      </div>
    </div>
  </section>
  <!-- // BLOG -->


  <!-- BOOKING -->
  <section id="booking">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          <?php
            $flash = get_flash();
            if($flash) echo '<div class="alert alert-info">'.htmlspecialchars($flash).'</div>';
            $user = null;
            if(!empty($_SESSION['user_id'])) $user = get_user_by_id($_SESSION['user_id']);
          ?>

          <?php if(!$user): ?>
            <div class="row">
              <div class="col-md-6">
                <h3>Create account</h3>
                <form method="post">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="register">
                  <div class="mb-2"><input name="full_name" class="form-control" placeholder="Full name" required></div>
                  <div class="mb-2"><input name="email" type="email" class="form-control" placeholder="Email" required></div>
                  <div class="mb-2"><input name="phone" class="form-control" placeholder="Phone"></div>
                  <div class="mb-2"><input name="password" type="password" class="form-control" placeholder="Password" required></div>
                  <div class="mb-2"><button class="btn btn-brand">Register</button></div>
                </form>
              </div>
              <div class="col-md-6">
                <h3>Login</h3>
                <form method="post">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="login">
                  <div class="mb-2"><input name="email" type="email" class="form-control" placeholder="Email" required></div>
                  <div class="mb-2"><input name="password" type="password" class="form-control" placeholder="Password" required></div>
                  <div class="mb-2"><button class="btn btn-outline-brand">Login</button></div>
                </form>
              </div>
            </div>
          <?php else: ?>
            <?php
              // edit mode?
              $editing = null;
              if(!empty($_GET['edit'])){
                  $editing = get_booking_by_id(intval($_GET['edit']));
                  // ensure ownership or admin
                  if($editing){
                      if($editing['user_id'] != $user['id'] && !$user['is_admin']){
                          $editing = null;
                      }
                  }
              }
            ?>
            <h2>Book Appointment</h2>
            <form method="post">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="<?php echo $editing ? 'edit_booking' : 'book'; ?>">
              <?php if($editing): ?><input type="hidden" name="booking_id" value="<?php echo (int)$editing['id']; ?>"><?php endif; ?>
              <div class="mb-2"><input name="full_name" class="form-control" placeholder="Full name" required value="<?php echo htmlspecialchars($editing['full_name'] ?? $user['full_name'] ?? ''); ?>"></div>
              <div class="mb-2"><input name="phone" class="form-control" placeholder="Phone" value="<?php echo htmlspecialchars($editing['phone'] ?? $user['phone'] ?? ''); ?>"></div>
              <div class="mb-2">
                <?php
                  $val = '';
                  if($editing){ $val = ($editing['booking_date'] . 'T' . substr($editing['booking_time'],0,5)); }
                ?>
                <input type="datetime-local" name="selectedDateTime" class="form-control" required value="<?php echo htmlspecialchars($val); ?>">
              </div>
              <div class="mb-2"><textarea name="message" class="form-control" rows="4" placeholder="Message"><?php echo htmlspecialchars($editing['message'] ?? ''); ?></textarea></div>
              <div class="mb-2">
                <button class="btn btn-brand"><?php echo $editing ? 'Update booking' : 'Submit booking'; ?></button>
                <?php if($editing): ?><a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-outline-brand">Cancel</a><?php endif; ?>
              </div>
            </form>

            <hr>
            <h4>Your bookings</h4>
            <?php $bookings = get_user_bookings($user['id']); ?>
            <?php if(!$bookings): ?>
              <p>No bookings yet.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-dark table-striped">
                  <thead><tr><th>#</th><th>Date</th><th>Time</th><th>Message</th><th>Actions</th></tr></thead>
                  <tbody>
                  <?php foreach($bookings as $b): ?>
                    <tr>
                      <td><?php echo (int)$b['id']; ?></td>
                      <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                      <td><?php echo htmlspecialchars(substr($b['booking_time'],0,5)); ?></td>
                      <td><?php echo htmlspecialchars(substr($b['message'],0,60)); ?></td>
                      <td>
                        <a class="btn btn-sm btn-outline-brand" href="?edit=<?php echo (int)$b['id']; ?>">Edit</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
  <!-- // BOOKING -->
  <!-- FOOTER -->
  <footer>
    <div class="footer-top">
      <div class="container">
        <div class="row gy-5">
          <div class="col-lg-4">
            <h3 class="mb-3"> <span>N</span>ishon </h3>
            <p>Thank you for booking our services, </p>
            <div class="social-links">
              <a href="" class="link-icon"><img src="./img/facebook.png"></a>
              <a href="" class="link-icon"><img src="./img/instagram.png"></a>
              <a href="" class="link-icon"><img src="./img/twitter.png"></a>
              <a href="" class="link-icon"><img src="./img/youtube.png"></a>

            </div>
          </div>
          <div class="col-lg-4 offset-lg-1 info">
            <h4 class="mb-3">Working hours</h4>
            <div class="">
              <h6>Monday - saturday</h6>
              <p>09:am - 11pm</p>
            </div>
            <div>
              <h6>Sunday</h6>
              <p>we're closed</p>
            </div>
          </div>
          <div class="col-lg-3 text-center">
            <h4>Contact</h4>
            <p>
              <a href="" class="link-icon"><img src="./img/magnifying-glass.png"></a>
              <span>24 M'royal street Nishon, wuse,ABUJA.</span>
            </p>
            <p>
              <a href="" class="link-icon"><img src="./img/telephone.png"></a>
              <span>+234 8077520921</span>
            </p>
            <p>
              <a href="" class="link-icon"><img src="./img/whatsapp.png"></a>
              <span>+234 8121457252</span>
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom ">
      <div class="container">
        <div class="row justify-content-between">
          <div class="col-auto">
            <p>copyrights@BigsAfrika. ALL Rights Reserved</p>
          </div>
           <div class="col-auto">
            <p>desinged by <a href="">Prince Uwanah</a></p>
          </div>
        </div>
      </div>
    </div>
  </footer>
  <!-- //FOOTER -->



  <script src="js/bootstrap.bundle.min.js"></script>

</body>

</html>