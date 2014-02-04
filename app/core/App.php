<?php

namespace core;

use core\components\resourceMapException;
use core\components\RouterException;
use core\components\BlueprintException;
use Symfony\Component\ClassLoader\UniversalClassLoader;

class App
{
  public static $container;

  public static function run()
  {
    try {
      self::autoloader();
      self::init();

      // IS HOME ? -- config home route ?!
      if (self::$container['Blueprint']->pathInfo != '/') {

        // LOGIC
        if (self::$container['Blueprint']->exist['logic']) {
          self::$container['Blueprint']->type = 'logic';

          if (self::$container['Blueprint']->isLogic()) {

            self::$container['Router']->logicRouting();
            self::$container['Blueprint']->lockRouter = true;
          } elseif (self::$container['Blueprint']->isSubLogic()) {

            self::$container['Router']->subLogicRouting();
            self::$container['Blueprint']->lockRouter = true;
          }

        }
        // END LOGIC

        // PHYSICAL
        if (self::$container['Blueprint']->exist['physical'] && !self::$container['Blueprint']->lockRouter) {
          if (empty(self::$container['Blueprint']->type))
            self::$container['Blueprint']->type = 'physical';

          if (self::$container['Blueprint']->isRest()) {

            self::$container['Router']->restRouting();
            self::$container['Blueprint']->lockRouter = true;
          } elseif (self::$container['Blueprint']->isRest()) {

          }
        }
        // END PHYSICAL

      } else {
        // home
      }

      if (self::$container['Blueprint']->exist['logic'] || self::$container['Blueprint']->exist['physical']) {
        try {
          self::$container['Router']->execute();
        } catch (RouterException $e) {
          // bad route for this resource !
          var_dump($e);
        }
      } else {
        // no resource
        echo "no resource";
      }

        /**
         * Make verif if is ajax request. If TRUE disable cache.
         * Browser like I.E sometimes download the response in his cache
         * and it never actualize the response again !!! Looks like that
         *
         * if (self::$container['Request']->isAjax()) {
         *   self::$container['Response']->cache(FALSE);
         * }
         */

      // with die at TRUE and erasePrevBuffer at TRUE the buffer will contain only this response
      // if not all old or/and next content in buffer will be append
      $params = array(
        'die' => FALSE,
        'erasePrevBuffer' => FALSE,
      );

      // SEND RESPONSE
      self::$container['Response']->sendResponse($params);

    } catch (ContainerException $e) {
      var_dump($e);
    } catch (resourceMapException $e) {
      var_dump($e);
    }
  }

  public static function init()
  {
    self::$container = Container::getInstance();
    self::$container->loaders();
    self::$container['Database'];
  }

  public static function autoloader()
  {
    if (file_exists('vendors/autoload.php'))
      require_once 'vendors/autoload.php';
    elseif (file_exists('../vendors/autoload.php'))
      require_once '../vendors/autoload.php';

    $loader = new UniversalClassLoader();
    $loader->useIncludePath(true);
    $loader->register();
    $loader->registerNamespaces(array(
      "core" => "./app/",
      "models" => "../"
    ));

    // Flush output
    if (ob_get_length() > 0) {
      self::$container['Response']->write(ob_get_clean());
    }

    // Enable ouput buffering
    ob_start();

    //self::stop();
  }

  public static function stop($code = 200)
  {
    self::$container['Response']->status($code)
        ->write(ob_get_clean())
        ->send();
  }

}

