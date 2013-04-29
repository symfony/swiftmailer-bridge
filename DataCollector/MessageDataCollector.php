<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Swiftmailer\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * MessageDataCollector.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Clément JOBEILI <clement.jobeili@gmail.com>
 */
class MessageDataCollector extends DataCollector
{
    private $container;

    /**
     * Constructor.
     *
     * We don't inject the message logger and mailer here
     * to avoid the creation of these objects when no emails are sent.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'mailer' => array(),
            'messageCount' => 0,
            'defaultMailer' => '',
        );
        // only collect when Swiftmailer has already been initialized
        if (class_exists('Swift_Mailer', false)) {
            $mailers = $this->container->getParameter('swiftmailer.mailers');
            foreach ($mailers as $name => $mailer) {
                if ($this->container->getParameter('swiftmailer.default_mailer') == $name || 'default' == $name) {
                    $this->data['defaultMailer'] = $name;
                }
                $loggerName = sprintf('swiftmailer.mailer.%s.plugin.messagelogger', $name);
                if ($this->container->has($loggerName)) {
                    $logger = $this->container->get($loggerName);
                    $this->data['mailer'][$name] = array(
                        'messages' => $logger->getMessages(),
                        'messageCount' => $logger->countMessages(),
                        'isSpool' => $this->container->getParameter(sprintf('swiftmailer.mailer.%s.spool.enabled', $name)),
                    );
                    $this->data['messageCount'] += $logger->countMessages();
                }
            }
        }
    }

    public function getMailers()
    {
        return array_keys($this->data['mailer']);
    }

    public function getMessageCount($name = null)
    {
        return is_null($name) ? $this->data['messageCount'] : $this->data['mailer'][$name]['messageCount'];
    }

    public function getMessages($name)
    {
        return $this->data['mailer'][$name]['messages'];
    }

    public function isSpool($name)
    {
        return $this->data['mailer'][$name]['isSpool'];
    }

    public function isDefaultMailer($name)
    {
        return $this->data['defaultMailer'] == $name ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'swiftmailer';
    }
}
