# pvlima/img-manager

Plugin para gerenciamento e upload de imagens. Muito simples e fácil de utilizar em qualquer projeto.

## Instalação

   É recomendável instalar o pacote usando o composer. Basta digitar o seguinte comando no terminal:
      
      composer require pvlima/img-manager

## Pré-requisitos
   É necessário estar com a biblioteca GD ativada no php
   
   Descomentar a seguinte linha no arquivo php.ini
   
      ;extension=php_gd2.dll
   Basta retirar o ponto e vírgula ";" no início da linha e em seguida, reiniciar o servidor

## Exemplo:

   ### Formulário HTML

    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="img">
        <input type="submit" value="Enviar" name="submit">
    </form>
    
   ### Arquivo PHP
   
   O construtor da classe Pvlima\ImgManager\ImgManager() deve receber uma imagem codificada em base64, pois agiliza todo o processo, além de facilitar a implementação com plugins javascript no front-end, já que a maioria deles repassa ao back-end justamente uma imagem codificada em base64.
   Caso você não esteja trabalhando com nenhum plugin no front-end (como plugins de corte, por exemplo) você deve codificar a imagem no próprio PHP. Como no exemplo:

    include 'vendor/autoload.php';

    $data = base64_encode(file_get_contents($_FILES['img']['tmp_name']));

    $img = new Pvlima\ImgManager\ImgManager($data);
    
    #Diretório onde a imagem vai ser salva
    $img->setFilePath(__DIR__ . '/imgs');
    
    #Salva a imagem e retorna o nome do arquivo
    echo $img->save();
    
   ### Outras Configurações
   
    include 'vendor/autoload.php';

    $data = base64_encode(file_get_contents($_FILES['img']['tmp_name']));

    $img = new Pvlima\ImgManager\ImgManager($data);
    
   O segundo parâmetro de ->setFilePath() é o nome personalizado do arquivo (Opcional)
   
      $img->setFilePath(__DIR__ . '/imgs', 'nome-da-imagem')
   Você pode também especificar a extensão de saída da imagem (".png", ".gif", ".wbmp"). O padrão é ".jpg"
   
    ->setExt('.png')
    
   Resolução da imagem (largura, altura) em pixels. O segundo parâmetro (altura) é opcional, ou seja, se não for informado, será calculado um valor proporcional à imagem original. Se for informado e não estiver dentro das proporções, a imagem porderá ser cortada mirando o centro, a não ser que a função ->distort() seja chamada logo em seguida, pois nesse caso, a imagem será distorcida.
   
    ->setResolution(640)
    
   OU
   
    ->setResolution(640, 800)
    
   Para que a imagem sejá distorcida e não seja cortada, caso os valores de largura e altura não estiverem nas proporções:
   
    ->distort()
    
   Salva a imagem e retorna o nome do arquivo
   
    echo $img->save();
