## 
# Helps Manage Environment Replication in a Vagrant Pod VM
# https://podman.readthedocs.io/
# https://www.vagrantup.com/docs/
#
# You do not need Ruby on the guest for this module.
# You should not need Ruby installed on the host, 
# aside from the runtime built into Vagrant
# see ../Vagrantfile for how this module is used
#
# Based on earlier work on Mr https://github.com/jthurteau/mr
# Copyright 2022 Troy Hurteau Under GPL-3.0 License
# https://github.com/jthurteau/jthurteau.github.io/blob/main/COPYRIGHT

module Tm
  extend self

  require_relative 'tm/utils'

  ##
  # where Tm runs from and aquires global(for intneral)/external recipes
  # for an "internal" tm project build, my_path and active_path are the same
  @my_path = File.dirname(__FILE__)

  ##
  # base path for the repo
  @base_path = File.dirname(File.dirname(__FILE__))

  ##
  # what VM image Tm should use as a base
  # https://app.vagrantup.com/boxes/search?utf8=%E2%9C%93&sort=downloads&provider=&q=alpine
  @default_platform = 'generic/alpine318'

  ##
  # what VM image Tm should use as a base
  # https://app.vagrantup.com/boxes/search?utf8=%E2%9C%93&sort=downloads&provider=&q=alpine
  @platform = nil

  ##
  # default secret generation length
  @secret_length = 16

  ##
  # default characters for secret generation
  @secret_set = '0123456789abcedf'

  ##
  # storage for the local secret
  @secret_file = 'secret.txt'

  ##
  # local secret
  @my_secret = nil

  ##
  # config file path
  @config_path = ''

  ##
  # seed files
  @config_files = []

  ##
  # project name
  @project = 'dev-container'

  ##
  # path to store temp/local project files between environments
  @project_path = nil

  ##
  # path to store temp/local project files between environments
  @developer_path = nil

  ##
  # vm name
  @vm_name = '[project]_sandbox'

  ##
  # path for install files
  @build_path = 'src/install'

  ##
  # autodetect build_path
  @auto_build_path = true;

  ##
  # path for shell provisioners
  @shell_path = '[build_path]/shell'

  ##
  #
  @sample_token = 'sample.'

  ##
  #
  @local_token = 'local-dev.'

  ##
  # auto running provisioners ('once')
  @auto = []
  
  ##
  # manually running provisioners ('never')
  @manual = []

  ##
  # auto running provisioners ('reload and up')
  @reload = []

  ##
  # variablely running provisioners (true = 'once', false = 'never')
  @variable = []

  ##
  # local folders to mount into the VM
  @shared = []

  ##
  # developer (local) defined facts to override configuration and set envinronment
  @local_facts = nil

  def self.init(config, developer_path = nil)
    @developer_path = TmUtils::path_safe(developer_path) if developer_path.is_a?(String)
    @local_facts = @developer_path ? TmUtils::load("#{@developer_path}/developer.yaml") : nil
    self._config(config, @local_facts)

    @base_path = TmUtils::base(caller)
    if (@auto_build_path && @my_path.start_with?(@base_path))
      @build_path = @my_path.slice((@base_path.length + 1)..-1)
    end
    @project_path = "projects/#{@local_token}#{@project}"
    secret_path = "#{@my_path}/#{@project_path}/#{@secret_file}"
    TmUtils::assert_path("#{@my_path}/#{@project_path}")
    @my_secret = TmUtils::assert_secret(secret_path, @secret_length, @secret_set)
    @vm_name = TmUtils::name_safe(TmUtils::sub(@vm_name,self._vars())) #TODO loop this
    @shell_path = TmUtils::name_safe(TmUtils::sub(@shell_path,self._vars()))
    #TmUtils::trace(@vm_name)
    TmUtils::assert_config_files(@config_files, @config_path, @sample_token)
  end

  def self.project
    @project
  end

  def self.platform
    @platform ? @platform : @default_platform
  end

  def self.provision(p, vm)
    #p.name = @vm_name if @singleton
    #TmUtils.trace(TmUtils::bind(@auto, self._vars))
    auto_flags = [true,'once','auto']
    manual_flags = [false,'never','manual']
    # TmUtils::trace(@manual + TmUtils::matching(@variable, manual_flags))
    # TmUtils::trace(@auto + TmUtils::matching(@variable, auto_flags))
    TmUtils::bind(@auto + TmUtils::matching(@variable, auto_flags), self._vars).each() {|a| self._add(vm, a, 'once')}
    TmUtils::bind(@manual + TmUtils::matching(@variable, manual_flags), self._vars).each() {|m| self._add(vm, m)}
    TmUtils::bind(@reload, self._vars).each() {|a| self._add(vm, a, 'always')}
  end

  def self.path()
    @my_path
  end

  def self.share_sources(vm, s = nil)
    s = @shared if s.nil?
    s = TmUtils::enforce_enumerable(s)
    s.each() do |m|
      m = ['.', '/vagrant'] if m == :main
      v = TmUtils::fs_map(m, @local_facts)
      #TmUtils::trace(v)
      if !v.nil?
        vm.synced_folder v[:l], v[:r], owner: 'vagrant', group: 'vagrant' 
      end
    end
  end

  def self.network(vm, local_port, remote_port, exposed)
    local_port = 80 if local_port == :web
    if (exposed)
      vm.network :forwarded_port, guest: local_port, host: remote_port
    else 
      vm.network :forwarded_port, guest: local_port, host: remote_port, host_ip: '127.0.0.1'
    end
  end

  #################################################################
    private
  #################################################################

  def self._config(c, o = nil)
    vars = self._vars()
    #block_sideload = [:local_facts]
    TmUtils.sym_keys(c).each() {|k,v| self._set(k, o && o.include?(k) ? o[k] : v, vars)}
    return if o.nil?
    #TmUtils.sym_keys(o).each() {|k,v| self._set(k, v, vars) if !block_sideload.include?(k)}
    TmUtils.sym_keys(o).each() {|k,v| self._set(k, v, vars)}
  end

  def self._vars()
    { #NOTE to support :vars binding, longer strings have to precede shorter matches
      'build_path': @build_path,
      'project_path': @project_path,
      'project': @project,
      'secret': @my_secret,
    }
  end

  def self._set(key, value, vars = [])
    #TODO track which values are getting overridden with local_facts
    case key
    when :project
      @project = TmUtils::name_safe(value)
    when :platform
      @platform = value
    when :manual_provisioners
      @manual = value
    when :auto_provisioners
      @auto = value
    when :reload_provisioners
      @reload = value
    when :variable_provisioners
      @variable = value
    when :auto_build_path
      @auto_build_path = value
    when :build_path
      @build_path = TmUtils::name_safe(value, true)
    when :shell_path
      @shell_path = TmUtils::name_safe(value, true)
    when :config_files
      @config_files = TmUtils::name_safe(value, true)
    when :secret_length
      @secret_length = value #TODO assert integer
    when :secret_set
      @secret_set = value
    when :secret_file
      @secret_file = TmUtils::name_safe(value, true)
    when :config_path
      @config_path = TmUtils::name_safe(value, true)
    when :config_files
      @config_files = value #TODO each
    when :vm_name
      @vm_name = TmUtils::name_safe(TmUtils::sub(value,vars))
    when :sample_token
      @sample_token = TmUtils::name_safe(value)
    when :local_token
      @local_token = TmUtils::name_safe(value)
    when :shared_sources
      @shared = value
    end
  end

  def self._add(vm, params, run_when = 'never')
    params = [params] if params.is_a?(String) && params.length > 0
    return if !params || !params.is_a?(Array) || params.length < 1
    name = params[0]
    file_base = params.length > 1 && params[1].is_a?(String) ? params[1]: name
    arg_short = params.length > 1 && params[1].class.include?(Enumerable) ? 1 : false
    arg_index = params.length > 2 ? 2 : arg_short
    args = arg_index ? params[arg_index] : [@my_secret]
    file = "#{file_base}.sh"
    vm.provision name, type: 'shell', path: "#{@shell_path}/#{file}", args: args, run: run_when
  end
  
end