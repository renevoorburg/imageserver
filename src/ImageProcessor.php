<?php

class ImageProcessor
{
    private Imagick $image;

    public function __construct(string $source_image)
    {
        $this->image = new Imagick($source_image);
    }

    public function autoRotateImage()
    {
        switch ($this->image->getImageOrientation()) {
            case imagick::ORIENTATION_BOTTOMRIGHT:
                $this->image->rotateimage("#000", 180); // rotate 180 degrees
                break;
            case imagick::ORIENTATION_RIGHTTOP:
                $this->image->rotateimage("#000", 90); // rotate 90 degrees CW
                break;
            case imagick::ORIENTATION_LEFTBOTTOM:
                $this->image->rotateimage("#000", -90); // rotate 90 degrees CCW
                break;
        }
        // ensure the EXIF data is correct:
        $this->image->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
    }

    public function contain($requested_width, $requested_height)
    {
        // 0 -preserve aspect ratio, fit image in contains of specified box:
        $new_width = $requested_width ;
        $new_height = $this->image->getImageHeight() * $new_width / $this->image->getImageWidth();
        if ($new_height > $requested_height) {
            $new_height = $requested_height;
            $new_width = $this->image->getImageWidth() * $new_height / $this->image->getImageHeight();
        }
        $this->image->resizeImage((int)$new_width, (int)$new_height, imagick::FILTER_LANCZOS, 1);
    }

    public function cover($requested_width, $requested_height)
    {
        // 6 -preserve aspect ratio, fit image to fully cover specified box:
        $factor_height = $requested_height / $this->image->getImageHeight();
        $factor_width = $requested_width / $this->image->getImageWidth();
        $factor = ($factor_height < $factor_width) ? $factor_width : $factor_height;
        $new_width = (int) ($this->image->getImageWidth() * $factor);
        $new_height = (int) ($this->image->getImageHeight() * $factor);

        $this->image->resizeImage((int)$new_width, (int)$new_height, imagick::FILTER_LANCZOS, 1);
    }

    public function crop($requested_width, $requested_height)
    {
        $requested_aspect_rate = $requested_width / $requested_height;
        $source_aspect_rate = $this->image->getImageWidth() / $this->image->getImageHeight();
        if ($requested_aspect_rate > $source_aspect_rate) {
            $trim = $this->image->getImageHeight() - ($this->image->getImageWidth() / $requested_aspect_rate);
            $this->image->cropImage((int)$this->image->getImageWidth(), (int)($this->image->getImageHeight() - $trim), 0, (int)($trim/2));
        } else {
            $trim = $this->image->getImageWidth() - ($this->image->getImageHeight() * $requested_aspect_rate);
            $this->image->cropImage((int)($this->image->getImageWidth() - $trim), (int)$this->image->getImageHeight(), (int)($trim/2), 0);
        }
        $this->image->resizeImage((int)$requested_width, (int)$requested_height, imagick::FILTER_LANCZOS, 1);
    }

    public function size($requested_width, $requested_height)
    {
        if (isset($requested_width) && !isset($requested_height)) {
            //  3 -preserve aspect ratio, fit width to specified box:
            $new_width = $requested_width;
            $new_height = $this->image->getImageHeight() * $requested_width / $this->image->getImageWidth();
        } elseif (isset($requested_height) && !isset($requested_width)) {
            // 4 -preserve aspect ratio, fit height to specified box:
            $new_height = $requested_height;
            $new_width = $this->image->getImageWidth() * $requested_height / $this->image->getImageHeight();
        } else {
            // 5 - fit to specified box, regardless the aspect ratio:
            $new_width = $requested_width;
            $new_height = $requested_height;
        }
        $this->image->resizeImage((int)$new_width, (int)$new_height, imagick::FILTER_LANCZOS, 1);
    }

    public function getHeader() : string
    {
        switch ($this->image->getImageFormat()) {
            case 'JPEG':
                return('Content-Type: image/jpeg');
            case 'PNG':
                return('Content-Type: image/png');
            case 'GIF':
                return('Content-Type: image/gif');
        }
        return '';
    }

    public function getImageBlob()
    {
        return $this->image->getImageBlob();
    }
}