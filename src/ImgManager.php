<?php

namespace Pvlima\ImgManager;

/**
 * Plugin para gerenciamento de imagens
 *
 * @author Paulo Vitor <pv.lima02@gmail.com>
 * @link http://pvlima.com.br/
 * @version 1.0.1
 */
class ImgManager
{
    /**
     * @var string imagem original codificada base64
     */
    private $data;

    /**
     * @var int largura da imagem original
     */
    private $srcWidth;

    /**
     * @var int altura da imagem original
     */
    private $srcHeight;

    /**
     * @var int largura da nova imagem
     */
    private $dstWidth;

    /**
     * @var int altura da nova imagem
     */
    private $dstHeight;

    /**
     * @var resource imagem original decodificada
     */
    private $srcImg;

    /**
     * @var resource nova imagem que será gerada
     */
    private $dstImg;

    /**
     * @var string Diretório onde a nova imagem será salva
     */
    private $filePath;

    /**
     * @var string Nome do arquivo da nova imagem que será salvo
     */
    private $filename;

    /**
     * @var string extensão da imagem criada
     */
    private $ext = '.jpg';

    /**
     * @var bool Distorcer a imagem
     */
    private $distort = false;

    /**
     * Recebe uma string de uma imagem codificada em base64
     * para que o processamento da imagem seja mais eficiente
     *
     * @param string $data
     * @return void
     */
    function __construct(string $data)
    {
        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $this->decodeImg($data);
    }

    /**
     * Decodifica a imagem e obtém informações sobre ela
     *
     * @param string $data
     * @return void
     * @throws \InvalidArgumentException - Caso o tipo de imagem não for suportado,
     * estiver corrompida ou o dado não for reconhecido
     */
    private function decodeImg(string $data)
    {
        $this->data = base64_decode($data);

        $this->srcImg = imagecreatefromstring($this->data);

        if(!$this->srcImg) throw new \InvalidArgumentException("Imagem inválida!");

        $size = getimagesizefromstring($this->data);
        $this->srcWidth = $size[0];
        $this->srcHeight = $size[1];
    }

    /**
     * Informa a extensão da imagem a ser gerada
     *
     * @param string $ext
     * @example '.jpg' | '.png' | '.gif' - Se não for informado, o padrão é .jpg
     * @return $this
     */
    public function setExt(string $ext)
    {
        $this->ext = $ext;

        return $this;
    }

    /**
     * Informa o diretório onde a imagem será salva
     *
     * @param string $filePath - Diretório válido
     * @param string $filename (Opcional) - informa o nome do arquivo que será gerado
     * @return $this
     * @throws \InvalidArgumentException - Caso não for informado um diretório válido
     */
    public function setFilePath(string $filePath, string $filename = null)
    {
        if(!is_dir($filePath))
            throw new \InvalidArgumentException("Diretório informado é inválido");
        
        if($filePath[strlen($filePath) -1] != '/')
            $filePath .= '/';

        $this->filePath = $filePath;
        $this->filename = ($filename)?preg_replace('/\.[^.]*$/', '', $filename):uniqid('img_');

        return $this;
    }

    /**
     * Informa a resolução da imagem em pixels
     * Se não for informado, serão considerados os valores da imagem original
     *
     * @param int $width - Largura da imagem
     * @param int $height - Altura da imagem
     * @return $this
     */
    public function setResolution(int $width, int $height = null)
    {
        $this->dstWidth = $width;
        $this->dstHeight = $height;

        return $this;
    }

    public function distort()
    {
        $this->distort = true;
        return $this;
    }

    /**
     * Gera a imagem
     *
     * @return mixed - Retorna o nome da imagem gerada em caso de sucesso ou false em caso de erro
     */
    public function save()
    {
        $src_x = 0;
        $src_y = 0;

        if($this->dstWidth){
            $this->srcImgScale();
            if($this->srcWidth < $this->dstWidth) $this->srcWidth = $this->dstWidth;
            if($this->srcWidth > $this->dstWidth) $src_x = floor($this->srcWidth - $this->dstWidth);
            if($this->srcHeight < $this->dstHeight) $this->srcHeight = $this->dstHeight;
            if($this->srcHeight > $this->dstHeight) $src_y = floor($this->srcHeight - $this->dstHeight);
        } else {
            $this->dstWidth = $this->srcWidth;
            $this->dstHeight = $this->srcHeight;
        }
        
        $this->createEmptyImg($this->dstWidth, $this->dstHeight);

        $copy = imagecopyresampled(
            $this->dstImg,
            $this->srcImg,
            0,
            0,
            $src_x,
            $src_y,
            $this->srcWidth,
            $this->srcHeight,
            $this->dstWidth,
            $this->dstHeight
        );
        
        if($copy)
            return $this->generateImg();

        return false;

    }

    ########    FUNÇÕES AUXILIARES    ########

    /**
     * Função para determinar a escala da imagem original de
     * acordo com a escala definida em setResolution
     *
     * @return void
     */
    private function srcImgScale()
    {
        $scaleHeight = $this->scaleHeight($this->dstWidth);

        if(!$this->dstHeight){
            $this->dstHeight = $scaleHeight;

            $this->srcWidth = $this->dstWidth;
            $this->srcHeight = $this->dstHeight;

        } else {

            if($this->distort === true || $this->dstHeight == $scaleHeight){
                $this->srcWidth = $this->dstWidth;
                $this->srcHeight = $this->dstHeight;

            } else {

                if($this->dstHeight > $scaleHeight){
                    $this->srcWidth = $this->scaleWidth($this->dstHeight);
                    $this->srcHeight = $this->dstHeight;
                }

                if($this->dstHeight < $scaleHeight){
                    $this->srcWidth = $this->dstWidth;
                    $this->srcHeight = $scaleHeight;
                }

            }
        }

        $this->srcImg = imagescale($this->srcImg, $this->srcWidth, $this->srcHeight);

    }

    /**
     * Retorna uma altura proporcional a largura informada no parâmetro
     * de acordo com os valores da proporção da imagem original obtidos
     * pelas variáveis $this->srcWidth e $this->srcHeight
     *
     * @param int $width - Largura
     * @return int $newHeight
     */
    private function scaleHeight(int $width)
    {
        $newHeight = (($this->srcHeight * $width) / $this->srcWidth);
        return round($newHeight);
    }

    /**
     * Retorna uma largura proporcional a altura informada no parâmetro
     * de acordo com os valores da proporção da imagem original obtidos
     * pelas variáveis $this->srcWidth e $this->srcHeight
     *
     * @param int $height - Altura
     * @return int $newWidth
     */
    private function scaleWidth(int $height)
    {
        $newWidth = (($this->srcWidth * $height) / $this->srcHeight);
        return round($newWidth);
    }

    /**
     * Cria um resource de imagem sem conteúdo
     *
     * @param int $width - Largura da imagem
     * @param int $height - Altura da imagem
     * @return void
     */
    private function createEmptyImg(int $width, int $height)
    {
        $this->dstImg = imagecreatetruecolor($width, $height);
    }

    /**
     * Gera a imagem e insere no diretório especificado
     *
     * @return mixed - Retorna o nome da imagem gerada em caso de sucesso ou false em caso de erro
     * @throws \InvalidArgumentException - Caso o diretório  não estiver definido
     */
    private function generateImg()
    {
        if(!$this->filePath)
            throw new \InvalidArgumentException("filePath deve ser informado pela função setFilePath()!");
            
        switch($this->ext){

            case '.jpg':
                $filename = $this->filename . $this->ext;
                if(imagejpeg($this->dstImg, $this->filePath . $filename))
                    return $filename;
                break;

            case '.png':
                $filename = $this->filename . $this->ext;
                if(imagepng($this->dstImg, $this->filePath . $filename))
                    return $filename;
                break;

            case '.gif':
                $filename = $this->filename . $this->ext;
                if(imagegif($this->dstImg, $this->filePath . $filename))
                    return $filename;
                break;

            case '.wbmp':
                $filename = $this->filename . $this->ext;
                if(imagewbmp($this->dstImg, $this->filePath . $filename))
                    return $filename;
                break;

            default:
                $filename = $this->filename . '.jpg';
                if(imagejpeg($this->dstImg, $this->filePath . $filename))
                    return $filename;
                break;

        }
        return false;
        
    }

    /**
     * Destrói da memória as imagens geradas
     *
     * @return void
     */
    function __destruct()
    {
        imagedestroy($this->srcImg);
	  	imagedestroy($this->dstImg);
    }

}
