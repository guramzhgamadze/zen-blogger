# Generates the wordpress.org icon and banner for Zen Blogger.
# Rendered at 2x and downscaled, so edges stay clean at the small sizes.
Add-Type -AssemblyName System.Drawing

$outDir = $args[0]
if ( -not $outDir ) { $outDir = "$PSScriptRoot\out" }
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

function C([int]$r, [int]$g, [int]$b, [int]$a = 255) { [System.Drawing.Color]::FromArgb($a, $r, $g, $b) }

function New-RoundRect([float]$x, [float]$y, [float]$w, [float]$h, [float]$r) {
    $p = New-Object System.Drawing.Drawing2D.GraphicsPath
    $d = 2 * $r
    $p.AddArc($x, $y, $d, $d, 180, 90)
    $p.AddArc($x + $w - $d, $y, $d, $d, 270, 90)
    $p.AddArc($x + $w - $d, $y + $h - $d, $d, $d, 0, 90)
    $p.AddArc($x, $y + $h - $d, $d, $d, 90, 90)
    $p.CloseFigure()
    return $p
}

function Save-Scaled([System.Drawing.Bitmap]$src, [int]$w, [int]$h, [string]$path) {
    $dst = New-Object System.Drawing.Bitmap($w, $h)
    $gs  = [System.Drawing.Graphics]::FromImage($dst)
    $gs.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $gs.PixelOffsetMode   = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $gs.SmoothingMode     = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $gs.DrawImage($src, (New-Object System.Drawing.Rectangle(0, 0, $w, $h)))
    $gs.Dispose()
    $dst.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
    $dst.Dispose()
    Write-Output ("  {0}  ({1}x{2})" -f (Split-Path $path -Leaf), $w, $h)
}

# A blog card: media band on top, three text rules under it.
function Draw-Card([System.Drawing.Graphics]$g, [float]$x, [float]$y, [float]$w, [float]$h, [int]$alpha, [float]$scale) {
    $r = 18 * $scale
    $card = New-RoundRect $x $y $w $h $r
    $g.FillPath((New-Object System.Drawing.SolidBrush((C 247 243 234 $alpha))), $card)

    $pad   = 14 * $scale
    $bandH = $h * 0.46
    $band     = New-RoundRect ($x + $pad) ($y + $pad) ($w - 2 * $pad) $bandH (10 * $scale)
    $bandAlpha = [int]($alpha * 0.85)
    $g.FillPath((New-Object System.Drawing.SolidBrush((C 26 122 116 $bandAlpha))), $band)

    $lineY = $y + $pad + $bandH + ($pad * 0.9)
    $lineH = 9 * $scale
    foreach ($frac in @(1.0, 0.82, 0.55)) {
        $lw = ($w - 2 * $pad) * $frac
        $ln        = New-RoundRect ($x + $pad) $lineY $lw $lineH ($lineH / 2)
        $lineAlpha = [int]($alpha * 0.55)
        $g.FillPath((New-Object System.Drawing.SolidBrush((C 60 74 74 $lineAlpha))), $ln)
        $lineY += $lineH * 2.1
    }
    $card.Dispose()
}

# ---------------------------------------------------------------- ICON
$S   = 512
$bmp = New-Object System.Drawing.Bitmap($S, $S)
$g   = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias

$rect = New-Object System.Drawing.Rectangle(0, 0, $S, $S)
$bg   = New-Object System.Drawing.Drawing2D.LinearGradientBrush($rect, (C 12 58 59), (C 24 118 112), 45)
$g.FillRectangle($bg, $rect)

# Soft highlight so the flat gradient does not read as a sticker.
$hl = New-Object System.Drawing.Drawing2D.GraphicsPath
$hl.AddEllipse(-120, -180, $S * 1.2, $S * 0.9)
$hlb = New-Object System.Drawing.Drawing2D.PathGradientBrush($hl)
$hlb.CenterColor = C 255 255 255 46
$hlb.SurroundColors = @((C 255 255 255 0))
$g.FillPath($hlb, $hl)

# Carousel: a centre card with its neighbours peeking in.
$sc = 1.7
Draw-Card $g 44  148 108 232 92  $sc
Draw-Card $g 360 148 108 232 92  $sc
Draw-Card $g 148 112 216 288 255 $sc

Save-Scaled $bmp 256 256 (Join-Path $outDir 'icon-256x256.png')
Save-Scaled $bmp 128 128 (Join-Path $outDir 'icon-128x128.png')
$g.Dispose(); $bmp.Dispose()

# -------------------------------------------------------------- BANNER
$W = 1544; $H = 500
$b2 = New-Object System.Drawing.Bitmap($W, $H)
$g2 = [System.Drawing.Graphics]::FromImage($b2)
$g2.SmoothingMode     = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$g2.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit

$r2  = New-Object System.Drawing.Rectangle(0, 0, $W, $H)
$bg2 = New-Object System.Drawing.Drawing2D.LinearGradientBrush($r2, (C 11 52 54), (C 24 118 112), 12)
$g2.FillRectangle($bg2, $r2)

$hl2 = New-Object System.Drawing.Drawing2D.GraphicsPath
$hl2.AddEllipse(820, -260, 900, 760)
$hb2 = New-Object System.Drawing.Drawing2D.PathGradientBrush($hl2)
$hb2.CenterColor = C 255 255 255 40
$hb2.SurroundColors = @((C 255 255 255 0))
$g2.FillPath($hb2, $hl2)

$title = New-Object System.Drawing.Font('Segoe UI', 76, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
$sub   = New-Object System.Drawing.Font('Segoe UI', 31, [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel)
$meta  = New-Object System.Drawing.Font('Segoe UI', 25, [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel)

$g2.DrawString('Zen Blogger', $title, (New-Object System.Drawing.SolidBrush((C 247 243 234))), 96, 150)
$g2.DrawString('Accessible blog carousel and post grid for Elementor', $sub, (New-Object System.Drawing.SolidBrush((C 214 232 228 235))), 100, 258)
$g2.DrawString('Six skins  ·  deep query builder  ·  search and filter', $meta, (New-Object System.Drawing.SolidBrush((C 160 205 198))), 100, 312)

# Same carousel motif, kept clear of the text column.
$sb = 1.35
Draw-Card $g2 1000 168 96  200 88  $sb
Draw-Card $g2 1332 168 96  200 88  $sb
Draw-Card $g2 1096 132 216 272 255 $sb

Save-Scaled $b2 1544 500 (Join-Path $outDir 'banner-1544x500.png')
Save-Scaled $b2 772  250 (Join-Path $outDir 'banner-772x250.png')
$g2.Dispose(); $b2.Dispose()

Write-Output "done"
