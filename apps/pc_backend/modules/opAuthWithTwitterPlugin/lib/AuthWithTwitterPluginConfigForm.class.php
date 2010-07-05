<?php
class AuthWithTwitterPluginConfigForm extends sfForm
{
  protected $configs = array(
    'awt_consumer' => 'awt_consumer',
    'awt_secret' => 'awt_secret',
    'awt_host' => 'awt_host',
  );
  public function configure()
  {
    $this->setWidgets(array(
    'awt_consumer' => new sfWidgetFormInput(),
    'awt_secret' => new sfWidgetFormInput(),
    'awt_host' => new sfWidgetFormInput(),
    ));

    $this->setValidators(array(
    'awt_consumer' => new sfValidatorString(array(),array()),
    'awt_secret' => new sfValidatorString(array(),array()),
    'awt_host' => new sfValidatorString(array(),array()),
    ));

    $this->widgetSchema->setHelp('awt_consumer','consumer');
    $this->widgetSchema->setHelp('awt_secret','secret');
    $this->widgetSchema->setHelp('awt_host','host');

    foreach($this->configs as $k => $v)
    {
      $config = Doctrine::getTable('SnsConfig')->retrieveByName($v);

      if($config)
      {
        $this->getWidgetSchema()->setDefault($k,$config->getValue());
      }
    }
    $this->getWidgetSchema()->setNameFormat('awt[%s]');
  }
  public function save(){
    foreach($this->getValues() as $k => $v)
    {
      if(!isset($this->configs[$k]))
      {
        continue;
      }
      $config = Doctrine::getTable('SnsConfig')->retrieveByName($this->configs[$k]);
      if(!$config)
      {
        $config = new SnsConfig();
        $config->setName($this->configs[$k]);
      }
      $config->setValue($v);
      $config->save();
    }
  }
  public function validate($validator,$value,$arguments = array())
  {
    return $value;
  }
}
?>
