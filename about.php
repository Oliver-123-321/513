<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';

ensureSession();

$pageTitle = 'Snack Shop · About';
$currentPage = 'about';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <!-- 百度地图API -->
    <!-- 注意：百度地图主要服务于中国地区，对澳大利亚等海外地区支持有限 -->
    <!-- 获取AK步骤：1. 访问 http://lbsyun.baidu.com/ 2. 注册登录 3. 创建应用获取AK -->
    <!-- 将下面的"YOUR_BAIDU_MAP_AK"替换为您自己的API Key -->
    <script type="text/javascript">
        // 检查是否已提供百度地图AK，如果没有则使用备用方案
        var baiduMapAK = 'YOUR_BAIDU_MAP_AK';
        
        // 如果没有配置AK，尝试使用公开的测试AK（可能有限制）
        // 建议替换为您自己的AK以获得更好的服务
        if (baiduMapAK && baiduMapAK !== 'YOUR_BAIDU_MAP_AK') {
            // 加载百度地图API
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://api.map.baidu.com/api?v=3.0&ak=' + baiduMapAK + '&callback=initBaiduMap';
            script.onerror = function() {
                console.log('百度地图API加载失败，使用备用方案');
                setTimeout(function() {
                    showFallbackMap();
                }, 500);
            };
            document.head.appendChild(script);
        } else {
            // 如果没有AK，使用备用方案：显示静态地图或使用其他服务
            console.log('百度地图AK未配置，将使用备用地图方案');
            // 延迟加载备用地图，给用户时间配置AK
            setTimeout(function() {
                if (typeof window.BMap === 'undefined') {
                    showFallbackMap();
                }
            }, 2000);
        }
    </script>
    <style>
        [data-theme="dark"] {
            --bg: #1a1a1a;
            --text: #f0f0f0;
            --muted: #a0a0a0;
            --primary: #7fb85f;
            --primary-dark: #6fa84f;
            --card: #2a2a2a;
            --border: #3a3a3a;
            --accent: #3a3a2a;
            --shadow: 0 20px 45px rgba(0, 0, 0, 0.5);
        }
        
        .theme-toggle-btn {
            position: fixed;
            top: 120px;
            right: 2rem;
            background: var(--card);
            border: 2px solid var(--border);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            z-index: 100;
            font-size: 1.5rem;
        }
        
        .theme-toggle-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            transform: scale(1.1);
        }
        
        .about-wrapper {
            padding: 3rem 5vw;
        }
        .about-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .about-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .about-header h1 {
            font-size: 2.5rem;
            margin: 0 0 1rem 0;
            color: var(--text);
        }
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        .about-section {
            background: var(--card);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }
        .about-section h2 {
            font-size: 1.5rem;
            margin: 0 0 1rem 0;
            color: var(--text);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
        }
        .about-section p {
            color: var(--muted);
            line-height: 1.8;
            margin: 1rem 0;
        }
        .about-section ul {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }
        .about-section ul li {
            color: var(--muted);
            line-height: 1.8;
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }
        .about-section ul li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: bold;
        }
        .contact-info {
            margin: 1rem 0;
        }
        .contact-info p {
            margin: 0.5rem 0;
        }
        .contact-info strong {
            color: var(--text);
        }
        .map-container {
            background: var(--card);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .map-container h2 {
            font-size: 1.5rem;
            margin: 0 0 1.5rem 0;
            color: var(--text);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
        }
        .map-wrapper {
            position: relative;
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
        }
        .map-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        #baiduMap {
            width: 100%;
            height: 100%;
            border-radius: 12px;
        }
        #addressDisplay {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        [data-theme="dark"] #addressDisplay {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
        }
        @media (max-width: 768px) {
            .about-content {
                grid-template-columns: 1fr;
            }
            .about-header h1 {
                font-size: 2rem;
            }
            .theme-toggle-btn {
                top: 100px;
                right: 1rem;
                width: 45px;
                height: 45px;
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body class="page-about">
<?php include __DIR__ . '/partials/header.php'; ?>

<button class="theme-toggle-btn" id="themeToggle" aria-label="Toggle theme" title="Toggle dark/light mode">
    <span class="theme-icon">🌙</span>
</button>

<main class="about-wrapper">
    <div class="about-container">
        <div class="about-header">
            <h1>About Snack Shop</h1>
        </div>

        <div class="about-content">
            <div class="about-left">
                <div class="about-section">
                    <h2>Company Profile</h2>
                    <p>Snack Shop is an e-commerce platform dedicated to bringing you the finest selection of handmade snacks and beverages. We specialize in traditional Chinese snacks, including handmade tangyuan, rice cakes, nougat candies, qingtuan, and a variety of refreshing beverages. Our mission is to deliver authentic flavors and high-quality products directly to your door.</p>
                </div>

                <div class="about-section">
                    <h2>Our Mission</h2>
                    <p>We are committed to bringing the joy of traditional snacks into your everyday life. Every product is carefully crafted with attention to quality and authenticity. We believe that great snacks should be accessible to everyone, and we strive to make that possible through our online platform.</p>
                </div>

                <div class="about-section">
                    <h2>Contact Us</h2>
                    <div class="contact-info">
                        <p><strong>Business Hours:</strong><br>
                        Monday to Friday: 9:00 AM - 5:00 PM</p>
                        <p><strong>Email:</strong><br>
                        <a href="mailto:info@snackshop.com" style="color: var(--primary);">info@snackshop.com</a></p>
                        <p><strong>Address:</strong><br>
                        Sydney, NSW, Australia</p>
                    </div>
                </div>

                <div class="about-section">
                    <h2>Why Choose Us</h2>
                    <ul>
                        <li>Clear and concise interface</li>
                        <li>Mobile-friendly design</li>
                        <li>Easy to use and navigate</li>
                        <li>Fresh, handmade products</li>
                        <li>Fast and reliable delivery</li>
                    </ul>
                </div>
            </div>

            <div class="about-right">
                <div class="map-container">
                    <h2>Store Location</h2>
                    <div id="addressDisplay" style="margin-bottom: 1rem; padding: 0.75rem; background: var(--accent); border-radius: 8px; font-size: 0.9rem; color: var(--text);">
                        <strong>📍 当前地址：</strong><span id="currentAddress">正在加载...</span>
                    </div>
                    <div class="map-wrapper">
                        <div id="baiduMap"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
// WGS84坐标转BD09坐标的函数（百度地图使用BD09坐标系）
function wgs84ToBd09(lng, lat) {
    var x_PI = 3.14159265358979324 * 3000.0 / 180.0;
    var PI = 3.1415926535897932384626;
    var a = 6378245.0;
    var ee = 0.00669342162296594323;
    
    function transformLat(lng, lat) {
        var ret = -100.0 + 2.0 * lng + 3.0 * lat + 0.2 * lat * lat + 0.1 * lng * lat + 0.2 * Math.sqrt(Math.abs(lng));
        ret += (20.0 * Math.sin(6.0 * lng * PI) + 20.0 * Math.sin(2.0 * lng * PI)) * 2.0 / 3.0;
        ret += (20.0 * Math.sin(lat * PI) + 40.0 * Math.sin(lat / 3.0 * PI)) * 2.0 / 3.0;
        ret += (160.0 * Math.sin(lat / 12.0 * PI) + 320 * Math.sin(lat * PI / 30.0)) * 2.0 / 3.0;
        return ret;
    }
    
    function transformLng(lng, lat) {
        var ret = 300.0 + lng + 2.0 * lat + 0.1 * lng * lng + 0.1 * lng * lat + 0.1 * Math.sqrt(Math.abs(lng));
        ret += (20.0 * Math.sin(6.0 * lng * PI) + 20.0 * Math.sin(2.0 * lng * PI)) * 2.0 / 3.0;
        ret += (20.0 * Math.sin(lng * PI) + 40.0 * Math.sin(lng / 3.0 * PI)) * 2.0 / 3.0;
        ret += (150.0 * Math.sin(lng / 12.0 * PI) + 300.0 * Math.sin(lng / 30.0 * PI)) * 2.0 / 3.0;
        return ret;
    }
    
    var dLat = transformLat(lng - 105.0, lat - 35.0);
    var dLng = transformLng(lng - 105.0, lat - 35.0);
    var radLat = lat / 180.0 * PI;
    var magic = Math.sin(radLat);
    magic = 1 - ee * magic * magic;
    var sqrtMagic = Math.sqrt(magic);
    dLat = (dLat * 180.0) / ((a * (1 - ee)) / (magic * sqrtMagic) * PI);
    dLng = (dLng * 180.0) / (a / sqrtMagic * Math.cos(radLat) * PI);
    var mgLat = lat + dLat;
    var mgLng = lng + dLng;
    
    var z = Math.sqrt(mgLng * mgLng + mgLat * mgLat) + 0.00002 * Math.sin(mgLat * x_PI);
    var theta = Math.atan2(mgLat, mgLng) + 0.000003 * Math.cos(mgLng * x_PI);
    var bdLng = z * Math.cos(theta) + 0.0065;
    var bdLat = z * Math.sin(theta) + 0.006;
    
    return {lng: bdLng, lat: bdLat};
}

// 更新地址显示的函数
function updateAddressDisplay(address) {
    var addressElement = document.getElementById('currentAddress');
    if (addressElement) {
        addressElement.textContent = address || '地址获取中...';
    }
}

// 百度地图初始化函数
function initBaiduMap() {
    try {
        // 创建地图实例
        var map = new BMap.Map("baiduMap");
        
        // 悉尼坐标（WGS84）：经度151.2093, 纬度-33.8688
        // 转换为BD09坐标（百度地图使用BD09坐标系）
        var wgs84Point = {lng: 151.2093, lat: -33.8688};
        var bd09Point = wgs84ToBd09(wgs84Point.lng, wgs84Point.lat);
        var point = new BMap.Point(bd09Point.lng, bd09Point.lat);
        
        // 初始化地图，设置缩放级别（15级适合显示街道）
        map.centerAndZoom(point, 15);
        
        // 启用滚轮缩放
        map.enableScrollWheelZoom(true);
        
        // 启用双击缩放
        map.enableDoubleClickZoom(true);
        
        // 添加地图控件
        map.addControl(new BMap.NavigationControl({
            anchor: BMAP_ANCHOR_TOP_LEFT,
            type: BMAP_NAVIGATION_CONTROL_LARGE
        }));
        map.addControl(new BMap.ScaleControl({
            anchor: BMAP_ANCHOR_BOTTOM_LEFT
        }));
        map.addControl(new BMap.OverviewMapControl({
            anchor: BMAP_ANCHOR_BOTTOM_RIGHT,
            isOpen: false
        }));
        
        // 创建自定义图标
        var icon = new BMap.Icon("http://api.map.baidu.com/img/markers.png", new BMap.Size(23, 25), {
            offset: new BMap.Size(10, 25),
            imageOffset: new BMap.Size(0, 0)
        });
        
        // 创建标注
        var marker = new BMap.Marker(point, {icon: icon});
        map.addOverlay(marker);
        
        // 创建地址解析器（逆地理编码）
        var geoc = new BMap.Geocoder();
        
        // 获取地址并更新显示的函数
        function getAddress(pt) {
            // 显示加载状态
            updateAddressDisplay('正在获取地址...');
            
            // 使用逆地理编码获取地址
            geoc.getLocation(pt, function(result) {
                if (result) {
                    // 获取详细地址信息
                    var address = result.address || '';
                    var addressComponents = result.addressComponents;
                    
                    // 构建完整地址字符串
                    var fullAddress = 'Sydney, NSW, Australia';
                    
                    if (address && address.trim() !== '') {
                        // 如果百度地图返回了地址，使用它
                        fullAddress = address;
                    } else if (addressComponents) {
                        // 否则尝试从地址组件构建
                        var addrParts = [];
                        if (addressComponents.street) addrParts.push(addressComponents.street);
                        if (addressComponents.streetNumber) addrParts.push(addressComponents.streetNumber);
                        if (addressComponents.district) addrParts.push(addressComponents.district);
                        if (addressComponents.city) addrParts.push(addressComponents.city);
                        if (addressComponents.province) addrParts.push(addressComponents.province);
                        
                        if (addrParts.length > 0) {
                            fullAddress = addrParts.join(', ');
                        }
                    }
                    
                    // 如果地址为空，使用默认地址
                    if (!fullAddress || fullAddress.trim() === '') {
                        fullAddress = 'Sydney, NSW, Australia';
                    }
                    
                    // 更新地址显示区域
                    updateAddressDisplay(fullAddress);
                    
                    // 更新信息窗口内容
                    var infoContent = "<div style='padding: 10px; line-height: 1.6;'>" +
                        "<h3 style='margin: 0 0 8px 0; font-size: 16px; color: #333; font-weight: bold;'>Snack Shop</h3>" +
                        "<p style='margin: 0 0 5px 0; color: #666; font-size: 14px;'>📍 " + fullAddress + "</p>" +
                        "<p style='margin: 5px 0 0 0; font-size: 12px; color: #999;'>坐标: " + pt.lng.toFixed(6) + ", " + pt.lat.toFixed(6) + "</p>" +
                        "</div>";
                    infoWindow.setContent(infoContent);
                } else {
                    // 如果无法获取详细地址，显示坐标和默认地址
                    var coordAddress = 'Sydney, NSW, Australia (坐标: ' + pt.lng.toFixed(6) + ', ' + pt.lat.toFixed(6) + ')';
                    updateAddressDisplay(coordAddress);
                    
                    // 更新信息窗口
                    var defaultContent = "<div style='padding: 10px; line-height: 1.6;'>" +
                        "<h3 style='margin: 0 0 8px 0; font-size: 16px; color: #333; font-weight: bold;'>Snack Shop</h3>" +
                        "<p style='margin: 0 0 5px 0; color: #666; font-size: 14px;'>📍 Sydney, NSW, Australia</p>" +
                        "<p style='margin: 5px 0 0 0; font-size: 12px; color: #999;'>坐标: " + pt.lng.toFixed(6) + ", " + pt.lat.toFixed(6) + "</p>" +
                        "</div>";
                    infoWindow.setContent(defaultContent);
                }
            });
        }
        
        // 创建信息窗口
        var infoWindow = new BMap.InfoWindow(
            "<div style='padding: 10px; line-height: 1.6;'>" +
            "<h3 style='margin: 0 0 8px 0; font-size: 16px; color: #333; font-weight: bold;'>Snack Shop</h3>" +
            "<p style='margin: 0 0 5px 0; color: #666; font-size: 14px;'>📍 Sydney, NSW, Australia</p>" +
            "<p style='margin: 0; font-size: 12px; color: #999;'>正在获取详细地址...</p>" +
            "</div>",
            {
                width: 300,
                height: 130,
                title: "Store Location"
            }
        );
        
        // 点击标注时打开信息窗口并更新地址
        marker.addEventListener("click", function() {
            map.openInfoWindow(infoWindow, point);
            getAddress(point);
        });
        
        // 地图移动结束时实时更新地址（显示标注点的地址，而不是中心点）
        map.addEventListener("moveend", function() {
            // 更新标注点的地址
            getAddress(point);
        });
        
        // 地图缩放结束时实时更新地址
        map.addEventListener("zoomend", function() {
            // 更新标注点的地址
            getAddress(point);
        });
        
        // 地图拖拽开始时显示提示
        map.addEventListener("dragstart", function() {
            updateAddressDisplay('地图移动中...');
        });
        
        // 点击地图任意位置时，更新标注位置并显示该位置的地址
        map.addEventListener("click", function(e) {
            var clickPoint = e.point;
            // 更新标注位置
            marker.setPosition(clickPoint);
            point = clickPoint;
            // 获取新位置的地址
            getAddress(clickPoint);
            // 打开信息窗口
            map.openInfoWindow(infoWindow, clickPoint);
        });
        
        // 初始获取地址
        getAddress(point);
        
        // 默认打开信息窗口
        setTimeout(function() {
            map.openInfoWindow(infoWindow, point);
        }, 800);
        
    } catch (error) {
        console.error('百度地图初始化失败:', error);
        updateAddressDisplay('Sydney, NSW, Australia');
        // 如果初始化失败，显示备用地图
        showFallbackMap();
    }
}

// 备用方案：显示静态地图图片或使用在线地图服务
function showFallbackMap() {
    var mapContainer = document.getElementById('baiduMap');
    if (mapContainer && mapContainer.innerHTML.trim() === '') {
        // 使用22.png作为备用地图图片
        mapContainer.innerHTML = '<img src="22.png" alt="Store Location - Sydney, NSW, Australia" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">';
        updateAddressDisplay('Sydney, NSW, Australia');
    }
}

// 页面加载完成后，如果没有加载百度地图，则显示备用方案
window.addEventListener('load', function() {
    setTimeout(function() {
        var mapContainer = document.getElementById('baiduMap');
        if (mapContainer && (!window.BMap || typeof initBaiduMap === 'undefined')) {
            showFallbackMap();
        }
    }, 2000);
});

// 主题切换功能
(function() {
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle.querySelector('.theme-icon');
    const html = document.documentElement;
    
    // Get saved theme or default to light
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    // Apply saved theme
    if (currentTheme === 'dark') {
        html.setAttribute('data-theme', 'dark');
        themeIcon.textContent = '☀️';
    } else {
        html.removeAttribute('data-theme');
        themeIcon.textContent = '🌙';
    }
    
    // Toggle theme on button click
    themeToggle.addEventListener('click', function() {
        const isDark = html.getAttribute('data-theme') === 'dark';
        
        if (isDark) {
            html.removeAttribute('data-theme');
            themeIcon.textContent = '🌙';
            localStorage.setItem('theme', 'light');
        } else {
            html.setAttribute('data-theme', 'dark');
            themeIcon.textContent = '☀️';
            localStorage.setItem('theme', 'dark');
        }
    });
})();
</script>

</body>
</html>

