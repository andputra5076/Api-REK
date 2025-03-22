<?php
header("Content-Type: application/json");
require_once "../config/db.php";
require_once "../config/helper.php";

$apiList = [
    'users-login' => [
        'title' => 'Login API',
        'required_fields' => ['nohp'],
        'function' => 'login'
    ],
    'users-verify' => [
        'title' => 'Verify OTP API',
        'required_fields' => ['nohp', 'otp'],
        'function' => 'verifyOtp'
    ],
    'verifykyc' => [
        'title' => 'Verifykyc API',
        'required_fields' => ['token', 'nama', 'ktp', 'ktp_face'],
        'function' => 'verifykyc'
    ],
    'get-menu' => [
        'title' => 'Get Menu API',
        'required_fields' => [],
        'function' => 'getMenu'
    ],
    'get-slider' => [
        'title' => 'Get Slider API',
        'required_fields' => [],
        'function' => 'getSlider'
    ],
    'get-info' => [
        'title' => 'Get Info API',
        'required_fields' => [],
        'function' => 'getInfo'
    ],
    'transaksi-buyer' => [
        'title' => 'Transaksi Buyer API',
        'required_fields' => ['nama_barang', 'seller_id', 'fee', 'totalharga', 'buyer_id', 'nama_kategori'],
        'function' => 'transaksiBuyer'
    ],
    'transaksi-seller' => [
        'title' => 'Transaksi Seller API',
        'required_fields' => ['nama_barang', 'seller_id', 'fee', 'totalharga', 'buyer_id', 'nama_kategori'],
        'function' => 'transaksiSeller'
    ],
    'get-bankadmin' => [
        'title' => 'Get Bank Admin API',
        'required_fields' => [],
        'function' => 'getBankadmin'
    ],
    'get-list-transaksi' => [
        'title' => 'Get List Transaksi API',
        'required_fields' => ['token'],
        'function' => 'getListTransaksi'
    ],
    'get-one-transaksi' => [
        'title' => 'Get One Transaksi API',
        'required_fields' => ['token', 'id'],
        'function' => 'getOneTransaksi'
    ],
    'buat-laporan' => [
        'title' => 'Buat Laporan API',
        'required_fields' => ['id_users', 'id_transaksi', 'masalah'],
        'function' => 'buatLaporan'
    ],
    'list-laporan-users' => [
        'title' => 'List Laporan Users API',
        'required_fields' => ['id_users'],
        'function' => 'listLaporanUsers'
    ],
    'list-one-laporan' => [
        'title' => 'List One Laporan API',
        'required_fields' => ['id'],
        'function' => 'listOneLaporan'
    ],
    'bukti-kirimuang' => [
        'title' => 'Bukti Kirim Uang API',
        'required_fields' => ['buyer_id', 'bukti_kirimuang'],
        'function' => 'buktiKirimUang'
    ],
    'bukti-kirimbarang' => [
        'title' => 'Bukti Kirim Barang API',
        'required_fields' => ['seller_id', 'bukti_kirimbarang'],
        'function' => 'buktiKirimBarang'
    ],
    'check-status-verify' => [
        'title' => 'Check Status Verify API',
        'required_fields' => ['token'],
        'function' => 'checkStatusVerify'
    ],
    'add-info' => [
        'title' => 'Add Info API',
        'required_fields' => ['judul', 'isiinfo'],
        'function' => 'addInfo'
    ]
];

function getAvailableAPIs()
{
    global $apiList;

    // Ambil isi file saat ini untuk mencari daftar case dalam switch
    $fileContent = file_get_contents(__FILE__);
    preg_match_all("/case ['\"](.+?)['\"]:/", $fileContent, $matches);

    $result = [];

    foreach ($matches[1] as $apiName) {
        if (isset($apiList[$apiName])) {
            $method = empty($apiList[$apiName]["required_fields"]) ? "GET" : "POST";
            $result[$apiName] = [
                "title" => $apiList[$apiName]["title"],
                "endpoint" => "$apiName",
                "method" => $method,
                "required_fields" => $apiList[$apiName]["required_fields"]
            ];
        } else {
            // Jika API tidak terdaftar di $apiList, tambahkan sebagai unknown API
            $result[$apiName] = [
                "title" => "Unknown API",
                "endpoint" => "index.php?action=$apiName"
            ];
        }
    }

    return $result;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'users-login':
        login();
        break;
    case 'users-verify':
        verifyOtp();
        break;
    case 'verifykyc':
        verifyKyc();
        break;
    case 'check-status-verify':
        $token = $_POST['token'] ?? '';
        checkStatusVerify($token);
        break;
    case 'get-menu':
        getMenu();
        break;
    case 'get-slider':
        getSlider();
        break;
    case 'get-info':
        getInfo();
        break;
    case 'transaksi-buyer':
        transaksiBuyer();
        break;
    case 'transaksi-seller':
        transaksiSeller();
        break;
    case 'get-bankadmin':
        getBankadmin();
        break;
    case 'get-list-transaksi':
        getListTransaksi();
        break;
    case 'get-one-transaksi':
        getOneTransaksi();
        break;
    case 'buat-laporan':
        buatLaporan();
        break;
    case 'list-laporan-users':
        listLaporanUsers();
        break;
    case 'list-one-laporan':
        listOneLaporan();
        break;
    case 'bukti-kirimuang':
        buktiKirimUang();
        break;
    case 'bukti-kirimbarang':
        buktiKirimBarang();
        break;
    case 'add-info':
        addInfo();
        break;
    default:
        sendResponse("success", "Berikut daftar API yang tersedia:", getAvailableAPIs());
}

// 🔹 Login API
function login()
{
    global $conn;

    // 🔹 Definisi API Title & Required Fields
    $api_title = "Login API";
    $required_fields = ["nohp"];

    $json = file_get_contents("php://input");
    $data = json_decode($json, true) ?: $_POST;

    if (empty($data['nohp'])) {
        sendResponse("error", "Nomor HP wajib diisi", ["debug_raw" => $data]);
    }

    // 🔹 Ganti prefix 08 dengan 628
    if (substr($data['nohp'], 0, 2) === '08') {
        $data['nohp'] = '628' . substr($data['nohp'], 2);
    }

    // 🔹 Cek apakah nomor sudah ada di database
    $stmt = $conn->prepare("SELECT id, otp, token, verifkyc FROM users WHERE nohp = ?");
    $stmt->bind_param("s", $data['nohp']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 🔹 Generate OTP baru
        $otp = generateOtp();
        $token = bin2hex(random_bytes(32));
        $update = $conn->prepare("UPDATE users SET otp = ?, token = ?, updated_at = NOW() WHERE nohp = ?");
        $update->bind_param("sss", $otp, $token, $data['nohp']);
        $update->execute();

        // 🔹 Kirim pesan setelah generate OTP
        sendMessage($data['nohp'], "Selamat datang di layanan kami! Kode OTP Anda adalah **$otp**. Gunakan kode ini untuk melanjutkan proses verifikasi.");

        // 🔹 Jika verifkyc sudah 1, akun sudah terverifikasi
        if ($user['verifkyc'] == 1) {
            sendResponse("success", "Akun sudah terverifikasi. OTP berhasil dikirim", ["token" => $token]);
        } else {
            sendResponse("success", "OTP berhasil dikirim", ["token" => $token]);
        }
    } else {
        // 🔹 Jika nomor belum ada, buat akun baru dan kirim OTP
        $otp = generateOtp();
        $token = bin2hex(random_bytes(32));
        $status = 0;
        $verifkyc = 0;

        $insert = $conn->prepare("INSERT INTO users (nama, nohp, otp, status, ktp, ktp_face, verifkyc, token) VALUES ('', ?, ?, ?, '', '', ?, ?)");
        $insert->bind_param("ssiss", $data['nohp'], $otp, $status, $verifkyc, $token);
        $insert->execute();

        // 🔹 Kirim pesan setelah generate OTP
        sendMessage($data['nohp'], "Selamat datang di layanan kami! Kode OTP Anda adalah **$otp**. Gunakan kode ini untuk melanjutkan proses verifikasi.");

        sendResponse("success", "OTP berhasil dikirim", ["token" => $token]);
    }
}

function checkStatusVerify($token)
{
    global $conn;

    // 🔹 Cek apakah token valid
    $stmt = $conn->prepare("SELECT verifkyc, token FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "Token tidak valid");
        return;
    }

    $user = $result->fetch_assoc();
    $verifkyc_status = $user['verifkyc'];
    $token = $user['token'];

    sendResponse("success", "Status verifikasi KYC berhasil diambil", [
        "verifkyc" => $verifkyc_status,
        "token" => $token
    ]);
}

function addInfo()
{
    global $conn;

    $api_title = "Add Info API";
    $required_fields = ["judul", "isiinfo"];

    // 🔹 Pastikan metode adalah POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse("error", "Metode request harus POST");
        return;
    }

    // 🔹 Tangkap data dari `$_POST`
    $data = $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Simpan data info ke database
    $insert = $conn->prepare("INSERT INTO info (judul, isiinfo) VALUES (?, ?)");
    $insert->bind_param("ss", $data['judul'], $data['isiinfo']);
    $insert->execute();

    sendResponse("success", "Info berhasil ditambahkan", [
        "judul" => $data['judul'],
        "isiinfo" => $data['isiinfo']
    ]);
}

function sendMessage($number, $message)
{
    $url = "http://8.219.144.70:3000/send-message";
    $data = [
        "number" => $number,
        "message" => $message
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

// 🔹 Verify OTP API
function verifyOtp()
{
    global $conn;

    $json = file_get_contents("php://input");
    $data = json_decode($json, true) ?: $_POST;

    if (empty($data['nohp']) || empty($data['otp'])) {
        sendResponse("error", "Nomor HP dan OTP wajib diisi", ["debug_raw" => $data]);
        return;
    }

    // 🔹 Ganti prefix 08 dengan 628
    if (substr($data['nohp'], 0, 2) === '08') {
        $data['nohp'] = '628' . substr($data['nohp'], 2);
    }

    // 🔹 Cek OTP dan ambil data user
    $stmt = $conn->prepare("SELECT id, token, verifkyc FROM users WHERE nohp = ? AND otp = ?");
    $stmt->bind_param("ss", $data['nohp'], $data['otp']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 🔹 Hapus OTP setelah diverifikasi
        $update = $conn->prepare("UPDATE users SET otp = NULL, updated_at = NOW() WHERE nohp = ?");
        $update->bind_param("s", $data['nohp']);
        $update->execute();

        // 🔹 Simpan data user ke dalam session
        session_start();
        $_SESSION['user'] = $user;

        // 🔹 Ambil pesan dari kolom `verifkyc`
        $kyc_status = $user['verifkyc']; // Ambil nilai langsung dari database

        // 🔹 Jika verifkyc masih 0, kirim respon bahwa akun belum diverifikasi
        if ($kyc_status == 0) {
            sendResponse("success", "Silakan upload dahulu form yang disediakan untuk verifikasi KYC", [
                "token" => $user['token'],
                "verifkyc" => $kyc_status
            ]);
        } else {
            sendResponse("success", "Login berhasil", [
                "token" => $user['token'],
                "verifkyc" => $kyc_status
            ]);
        }
    } else {
        sendResponse("error", "OTP salah atau kadaluarsa");
    }
}

function uploadFileKyc($files, $fieldName, $uploadDir)
{
    if (isset($files[$fieldName]) && $files[$fieldName]['error'] === UPLOAD_ERR_OK) {
        $fileName = uniqid() . "_" . basename($files[$fieldName]['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($files[$fieldName]['tmp_name'], $filePath)) {
            return "uploads/" . $fileName; // Path yang akan disimpan di database
        } else {
            sendResponse("error", "Gagal mengupload $fieldName");
            exit;
        }
    } else {
        sendResponse("error", "$fieldName wajib diunggah");
        exit;
    }
}

function verifyKyc()
{
    global $conn;

    $api_title = "Verify KYC API";
    $required_fields = ["token", "nama", "ktp", "ktp_face"];

    // 🔹 Pastikan metode adalah POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse("error", "Metode request harus POST");
        return;
    }

    // 🔹 Tangkap data dari `$_POST`
    $data = $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field]) && !isset($_FILES[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Cek apakah token valid
    $stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->bind_param("s", $data['token']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "Token tidak valid");
        return;
    }

    $user = $result->fetch_assoc();

    // 🔹 Direktori penyimpanan file
    $uploadDir = __DIR__ . "/uploads/";

    // 🔹 Cek apakah folder uploads ada, jika tidak buat folder
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // 🔹 Upload file KTP dan KTP Face
    $ktpPath = uploadFileKyc($_FILES, 'ktp', $uploadDir);
    $ktpFacePath = uploadFileKyc($_FILES, 'ktp_face', $uploadDir);

    // 🔹 Tentukan status KYC (Pending atau Langsung Diverifikasi)
    $verifkyc_status = 1; // Bisa diganti ke "1" jika langsung diverifikasi

    // 🔹 Simpan data KYC ke database
    $update = $conn->prepare("UPDATE users SET nama = ?, ktp = ?, ktp_face = ?, verifkyc = ?, updated_at = NOW() WHERE token = ?");
    $update->bind_param("sssss", $data['nama'], $ktpPath, $ktpFacePath, $verifkyc_status, $data['token']);
    $update->execute();

    sendResponse("success", "KYC berhasil diajukan", [
        "token" => $data['token'],
        "ktp" => $ktpPath,
        "ktp_face" => $ktpFacePath,
        "verifkyc" => $verifkyc_status
    ]);
}

function getMenu()
{
    global $conn;

    $query = "SELECT * FROM menu";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $menu = [];
        while ($row = $result->fetch_assoc()) {
            $menu[] = $row;
        }
        sendResponse("success", "Data menu ditemukan", ["menu" => $menu]);
    } else {
        sendResponse("error", "Data menu tidak ditemukan");
    }
}

function getSlider()
{
    global $conn;

    $query = "SELECT * FROM slider";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $slider = [];
        while ($row = $result->fetch_assoc()) {
            $slider[] = $row;
        }
        sendResponse("success", "Data slider ditemukan", ["slider" => $slider]);
    } else {
        sendResponse("error", "Data slider tidak ditemukan");
    }
}

function getInfo()
{
    global $conn;

    $query = "SELECT * FROM info";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $info = [];
        while ($row = $result->fetch_assoc()) {
            $info[] = $row;
        }
        sendResponse("success", "Data info ditemukan", ["info" => $info]);
    } else {
        sendResponse("error", "Data info tidak ditemukan");
    }
}

function transaksiBuyer()
{
    global $conn;

    $api_title = "Transaksi Buyer API";
    $required_fields = ["nama_barang", "seller_id", "fee", "totalharga", "buyer_id", "nama_kategori"];

    $json = file_get_contents("php://input");
    $data = json_decode($json, true) ?: $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Cek seller_id dan ambil nohp seller
    $stmt = $conn->prepare("SELECT nohp FROM users WHERE nohp = ?");
    $stmt->bind_param("s", $data['seller_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "Seller ID tidak valid", ["seller_id" => $data['seller_id']]);
        return;
    }

    $seller = $result->fetch_assoc();
    $nohp_seller = $seller['nohp'];

    // 🔹 Cek buyer_id dan ambil nohp buyer
    $stmt = $conn->prepare("SELECT nohp FROM users WHERE nohp = ?");
    $stmt->bind_param("s", $data['buyer_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "Buyer ID tidak valid", ["buyer_id" => $data['buyer_id']]);
        return;
    }

    $buyer = $result->fetch_assoc();
    $nohp_buyer = $buyer['nohp'];

    // 🔹 Simpan data transaksi ke database
    $insert = $conn->prepare("INSERT INTO transaksi (nama_barang, buyer_id, fee, totalharga, seller_id, making_id, nama_kategori) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("siidsss", $data['nama_barang'], $nohp_buyer, $data['fee'], $data['totalharga'], $nohp_seller, $nohp_buyer, $data['nama_kategori']);
    $insert->execute();

    sendResponse("success", "Transaksi berhasil disimpan", [
        "nama_barang" => $data['nama_barang'],
        "buyer_id" => $nohp_buyer,
        "fee" => $data['fee'],
        "totalharga" => $data['totalharga'],
        "seller_id" => $nohp_seller,
        "making_id" => $nohp_buyer,
        "nama_kategori" => $data['nama_kategori']
    ]);
}

function transaksiSeller()
{
    global $conn;

    // $api_title = "Transaksi Seller API";
    $required_fields = ["nama_barang", "seller_id", "fee", "totalharga", "buyer_id", "nama_kategori"];

    $json = file_get_contents("php://input");
    $data = json_decode($json, true) ?: $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Cek seller_id dan ambil nohp seller
    $stmt = $conn->prepare("SELECT nohp FROM users WHERE nohp = ?");
    $stmt->bind_param("s", $data['seller_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "Seller ID tidak valid", ["seller_id" => $data['seller_id']]);
        return;
    }

    $seller = $result->fetch_assoc();
    $nohp_seller = $seller['nohp'];

    // 🔹 Cek buyer_id dan ambil nohp buyer
    $stmt = $conn->prepare("SELECT nohp FROM users WHERE nohp = ?");
    $stmt->bind_param("s", $data['buyer_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "Buyer ID tidak valid", ["buyer_id" => $data['buyer_id']]);
        return;
    }

    $buyer = $result->fetch_assoc();
    $nohp_buyer = $buyer['nohp'];

    // 🔹 Simpan data transaksi ke database
    $insert = $conn->prepare("INSERT INTO transaksi (nama_barang, buyer_id, fee, totalharga, seller_id, making_id, nama_kategori) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("siidsss", $data['nama_barang'], $nohp_buyer, $data['fee'], $data['totalharga'], $nohp_seller, $nohp_seller, $data['nama_kategori']);
    $insert->execute();

    sendResponse("success", "Transaksi berhasil disimpan", [
        "nama_barang" => $data['nama_barang'],
        "buyer_id" => $nohp_buyer,
        "fee" => $data['fee'],
        "totalharga" => $data['totalharga'],
        "seller_id" => $nohp_seller,
        "making_id" => $nohp_seller,
        "nama_kategori" => $data['nama_kategori']
    ]);
}

function getBankadmin()
{
    global $conn;

    $query = "SELECT * FROM bank_admin";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $info = [];
        while ($row = $result->fetch_assoc()) {
            $info[] = $row;
        }
        sendResponse("success", "Data bank ditemukan", ["info" => $info]);
    } else {
        sendResponse("error", "Data bank tidak ditemukan");
    }
}

function getListTransaksi()
{
    global $conn;

    // $api_title = "Get List Transaksi API";
    $required_fields = ["token"];

    $json = file_get_contents("php://input");
    $data = json_decode($json, true) ?: $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Cek token dan ambil data user
    $stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->bind_param("s", $data['token']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "Token tidak valid");
        return;
    }

    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // 🔹 Ambil data transaksi berdasarkan token
    $query = "SELECT t.* FROM transaksi t
              JOIN users u1 ON t.buyer_id = u1.nohp
              JOIN users u2 ON t.seller_id = u2.nohp
              WHERE u1.token = ? OR u2.token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $data['token'], $data['token']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $transaksi = [];
        while ($row = $result->fetch_assoc()) {
            $transaksi[] = $row;
        }
        sendResponse("success", "Data transaksi ditemukan", ["transaksi" => $transaksi]);
    } else {
        sendResponse("error", "Data transaksi tidak ditemukan");
    }
}

function getOneTransaksi()
{
    global $conn;

    $api_title = "Get One Transaksi API";
    $required_fields = ["token", "id"];

    $json = file_get_contents("php://input");
    $data = json_decode($json, true) ?: $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Cek token dan ambil data user
    $stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->bind_param("s", $data['token']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "Token tidak valid");
        return;
    }

    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // 🔹 Ambil data transaksi berdasarkan id dan token
    $query = "SELECT t.* FROM transaksi t
              JOIN users u1 ON t.buyer_id = u1.nohp
              JOIN users u2 ON t.seller_id = u2.nohp
              WHERE t.id = ? AND (u1.token = ? OR u2.token = ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $data['id'], $data['token'], $data['token']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $transaksi = $result->fetch_assoc();
        sendResponse("success", "Data transaksi ditemukan", ["transaksi" => $transaksi]);
    } else {
        sendResponse("error", "Data transaksi tidak ditemukan");
    }
}

function buatLaporan()
{
    global $conn;

    $api_title = "Buat Laporan API";
    $required_fields = ["id_users", "id_transaksi", "masalah"];

    $json = file_get_contents("php://input");
    $data = json_decode($json, true) ?: $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Cek id_users dan ambil data user
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $data['id_users']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "ID Users tidak valid");
        return;
    }

    // 🔹 Cek id_transaksi dan ambil data transaksi
    $stmt = $conn->prepare("SELECT id FROM transaksi WHERE id = ?");
    $stmt->bind_param("i", $data['id_transaksi']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "ID Transaksi tidak valid");
        return;
    }

    // 🔹 Simpan data laporan ke database
    $status = 0;
    $insert = $conn->prepare("INSERT INTO laporan (id_users, id_transaksi, masalah, status) VALUES (?, ?, ?, ?)");
    $insert->bind_param("iiss", $data['id_users'], $data['id_transaksi'], $data['masalah'], $status);
    $insert->execute();

    sendResponse("success", "Laporan berhasil disimpan", [
        "id_users" => $data['id_users'],
        "id_transaksi" => $data['id_transaksi'],
        "masalah" => $data['masalah'],
        "status" => $status
    ]);
}

function listLaporanUsers()
{
    global $conn;

    $api_title = "List Laporan Users API";
    $required_fields = ["id_users"];

    $json = file_get_contents("php://input");
    $data = json_decode($json, true) ?: $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Cek id_users dan ambil data user
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $data['id_users']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "ID Users tidak valid");
        return;
    }

    // 🔹 Ambil data laporan berdasarkan id_users
    $query = "SELECT * FROM laporan WHERE id_users = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $data['id_users']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $laporan = [];
        while ($row = $result->fetch_assoc()) {
            $laporan[] = $row;
        }
        sendResponse("success", "Data laporan ditemukan", ["laporan" => $laporan]);
    } else {
        sendResponse("error", "Data laporan tidak ditemukan");
    }
}

function listOneLaporan()
{
    global $conn;

    $api_title = "List One Laporan API";
    $required_fields = ["id"];

    $json = file_get_contents("php://input");
    $data = json_decode($json, true) ?: $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Ambil data laporan berdasarkan id
    $query = "SELECT * FROM laporan WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $data['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $laporan = $result->fetch_assoc();
        sendResponse("success", "Data laporan ditemukan", ["laporan" => $laporan]);
    } else {
        sendResponse("error", "Data laporan tidak ditemukan");
    }
}

function buktiKirimUang()
{
    global $conn;

    $required_fields = ["buyer_id"];

    // 🔹 Pastikan metode adalah POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse("error", "Metode request harus POST");
        return;
    }

    // 🔹 Tangkap data dari `$_POST`
    $data = $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Cek buyer_id dan ambil data transaksi
    $stmt = $conn->prepare("SELECT id FROM transaksi WHERE buyer_id = ?");
    $stmt->bind_param("s", $data['buyer_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "Buyer ID tidak valid");
        return;
    }

    // 🔹 Direktori penyimpanan file
    $uploadDir = __DIR__ . "/uploads/";

    // 🔹 Cek apakah folder uploads ada, jika tidak buat folder
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // 🔹 Upload file bukti kirim uang
    // 🔹 Fungsi untuk menyimpan file
    function uploadFile2($file, $fieldName, $uploadDir)
    {
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $fileName = uniqid() . "_" . basename($_FILES[$fieldName]['name']);
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $filePath)) {
                return "uploads/" . $fileName; // Path yang akan disimpan di database
            } else {
                sendResponse("error", "Gagal mengupload $fieldName");
                exit;
            }
        } else {
            sendResponse("error", "$fieldName wajib diunggah");
            exit;
        }
    }

    // 🔹 Upload file bukti kirim uang
    $buktiKirimUangPath = uploadFile2($_FILES, 'bukti_kirimuang', $uploadDir);

    // 🔹 Simpan bukti kirim uang dan update status transaksi
    $update = $conn->prepare("UPDATE transaksi SET bukti_kirimuang = ?, status = 2 WHERE buyer_id = ?");
    $update->bind_param("ss", $buktiKirimUangPath, $data['buyer_id']);
    $update->execute();

    sendResponse("success", "Bukti kirim uang berhasil disimpan", [
        "bukti_kirimuang" => $buktiKirimUangPath
    ]);
}

function buktiKirimBarang()
{
    global $conn;

    $api_title = "Bukti Kirim Barang API";
    $required_fields = ["seller_id"];

    // 🔹 Pastikan metode adalah POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse("error", "Metode request harus POST");
        return;
    }

    // 🔹 Tangkap data dari `$_POST`
    $data = $_POST;

    // 🔹 Cek apakah semua field wajib sudah diisi
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendResponse("error", "$field wajib diisi");
            return;
        }
    }

    // 🔹 Cek seller_id dan ambil data transaksi
    $stmt = $conn->prepare("SELECT id FROM transaksi WHERE seller_id = ?");
    $stmt->bind_param("s", $data['seller_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse("error", "Seller ID tidak valid");
        return;
    }

    // 🔹 Direktori penyimpanan file
    $uploadDir = __DIR__ . "/uploads/";

    // 🔹 Cek apakah folder uploads ada, jika tidak buat folder
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // 🔹 Fungsi untuk menyimpan file
    function uploadFile($file, $fieldName, $uploadDir)
    {
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $fileName = uniqid() . "_" . basename($_FILES[$fieldName]['name']);
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $filePath)) {
                return "uploads/" . $fileName; // Path yang akan disimpan di database
            } else {
                sendResponse("error", "Gagal mengupload $fieldName");
                exit;
            }
        } else {
            sendResponse("error", "$fieldName wajib diunggah");
            exit;
        }
    }

    // 🔹 Upload file bukti kirim barang
    $buktiKirimBarangPath = uploadFile($_FILES, 'bukti_kirimbarang', $uploadDir);

    // 🔹 Simpan bukti kirim barang dan update status transaksi
    $update = $conn->prepare("UPDATE transaksi SET bukti_kirimbarang = ?, status = 1 WHERE seller_id = ?");
    $update->bind_param("ss", $buktiKirimBarangPath, $data['seller_id']);
    $update->execute();

    sendResponse("success", "Bukti kirim barang berhasil disimpan", [
        "bukti_kirimbarang" => $buktiKirimBarangPath
    ]);
}
