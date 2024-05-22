<?php

class ImageProcessor extends Imagick
{

    public function __construct(string $source_image)
    {
        parent::__construct($source_image);
        $this->autoRotateImage();
    }

    public function contain(int $requested_width, int $requested_height)
    {
        // 0 -preserve aspect ratio, fit image in contains of specified box:
        $new_width = $requested_width ;
        $new_height = $this->getImageHeight() * $new_width / $this->getImageWidth();
        if ($new_height > $requested_height) {
            $new_height = $requested_height;
            $new_width = $this->getImageWidth() * $new_height / $this->getImageHeight();
        }
        $this->resizeImage((int)$new_width, (int)$new_height, imagick::FILTER_LANCZOS, 1);
    }

    public function cover(int $requested_width, int $requested_height)
    {
        // 6 -preserve aspect ratio, fit image to fully cover specified box:
        $factor_height = $requested_height / $this->getImageHeight();
        $factor_width = $requested_width / $this->getImageWidth();
        $factor = ($factor_height < $factor_width) ? $factor_width : $factor_height;
        $new_width = (int) ($this->getImageWidth() * $factor);
        $new_height = (int) ($this->getImageHeight() * $factor);

        $this->resizeImage((int)$new_width, (int)$new_height, imagick::FILTER_LANCZOS, 1);
    }

    public function crop(int $requested_width, int $requested_height)
    {
        $requested_aspect_rate = $requested_width / $requested_height;
        $source_aspect_rate = $this->getImageWidth() / $this->getImageHeight();
        if ($requested_aspect_rate > $source_aspect_rate) {
            $trim = $this->getImageHeight() - ($this->getImageWidth() / $requested_aspect_rate);
            $this->cropImage((int)$this->getImageWidth(), (int)($this->getImageHeight() - $trim), 0, (int)($trim/2));
        } else {
            $trim = $this->getImageWidth() - ($this->getImageHeight() * $requested_aspect_rate);
            $this->cropImage((int)($this->getImageWidth() - $trim), (int)$this->getImageHeight(), (int)($trim/2), 0);
        }
        $this->resizeImage((int)$requested_width, (int)$requested_height, imagick::FILTER_LANCZOS, 1);
    }

    public function size(?int $requested_width, ?int $requested_height)
    {
        if (isset($requested_width) && !isset($requested_height)) {
            //  3 -preserve aspect ratio, fit width to specified box:
            $new_width = $requested_width;
            $new_height = $this->getImageHeight() * $requested_width / $this->getImageWidth();
        } elseif (isset($requested_height) && !isset($requested_width)) {
            // 4 -preserve aspect ratio, fit height to specified box:
            $new_height = $requested_height;
            $new_width = $this->getImageWidth() * $requested_height / $this->getImageHeight();
        } else {
            // 5 - fit to specified box, regardless the aspect ratio:
            $new_width = $requested_width;
            $new_height = $requested_height;
        }
        $this->resizeImage((int)$new_width, (int)$new_height, imagick::FILTER_LANCZOS, 1);
    }

    public function getHeader() : string
    {
        switch ($this->getImageFormat()) {
            case 'JPEG':
                return('Content-Type: image/jpeg');
            case 'PNG':
                return('Content-Type: image/png');
            case 'GIF':
                return('Content-Type: image/gif');
        }
        return '';
    }

    private function autoRotateImage()
    {
        switch ($this->getImageOrientation()) {
            case imagick::ORIENTATION_BOTTOMRIGHT:
                $this->rotateimage("#000", 180); // rotate 180 degrees
                break;
            case imagick::ORIENTATION_RIGHTTOP:
                $this->rotateimage("#000", 90); // rotate 90 degrees CW
                break;
            case imagick::ORIENTATION_LEFTBOTTOM:
                $this->rotateimage("#000", -90); // rotate 90 degrees CCW
                break;
        }
        // ensure the EXIF data is correct:
        $this->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
    }

}