<?php

namespace Consolidation\SiteProcess\Transport;

use Consolidation\SiteProcess\SiteProcess;
use Consolidation\SiteProcess\Util\Escape;
use Consolidation\SiteAlias\SiteAliasInterface;
use Consolidation\SiteProcess\Util\Shell;
use Consolidation\Config\ConfigInterface;

/**
 * SshTransport knows how to wrap a command such that it runs on a remote
 * system via the ssh cli.
 */
class SshTransport implements TransportInterface
{
    protected $tty;
    protected $siteAlias;

    public function __construct(SiteAliasInterface $siteAlias)
    {
        $this->siteAlias = $siteAlias;
    }

    /**
     * @inheritdoc
     */
    public function configure(SiteProcess $process)
    {
        $this->tty = $process->isTty();
    }

    /**
     * inheritdoc
     */
    public function wrap($args)
    {
        $transport = ['ssh'];
        $transportOptions = $this->getTransportOptions();
        $commandToExecute = $this->getCommandToExecute($args);

        return array_merge(
            $transport,
            $transportOptions,
            $commandToExecute
        );
    }

    /**
     * @inheritdoc
     */
    public function addChdir($cd_remote, $args)
    {
        return array_merge(
            [
                'cd',
                $cd_remote,
                Shell::op('&&'),
            ],
            $args
        );
    }

    /**
     * getTransportOptions returns the transport options for the tranport
     * mechanism itself
     */
    protected function getTransportOptions()
    {
        $transportOptions = [
            Shell::preEscaped($this->siteAlias->get('ssh.options', '-o PasswordAuthentication=no')),
            $this->siteAlias->remoteHostWithUser(),
        ];
        if ($this->tty) {
            array_unshift($transportOptions, '-t');
        }
        return $transportOptions;
    }

    /**
     * getCommandToExecute processes the arguments for the command to
     * be executed such that they are appropriate for the transport mechanism.
     */
    protected function getCommandToExecute($args)
    {
        // Escape each argument for the target system and then join
        $args = Escape::argsForSite($this->siteAlias, $args);
        $commandToExecute = implode(' ', $args);

        return [$commandToExecute];
    }
}
