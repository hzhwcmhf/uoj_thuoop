# CMAKE generated file: DO NOT EDIT!
# Generated by "Unix Makefiles" Generator, CMake Version 3.5

# Delete rule output on recipe failure.
.DELETE_ON_ERROR:


#=============================================================================
# Special targets provided by cmake.

# Disable implicit rules so canonical targets will work.
.SUFFIXES:


# Remove some rules from gmake that .SUFFIXES does not remove.
SUFFIXES =

.SUFFIXES: .hpux_make_needs_suffix_list


# Suppress display of executed commands.
$(VERBOSE).SILENT:


# A target that is always out of date.
cmake_force:

.PHONY : cmake_force

#=============================================================================
# Set environment variables for the build.

# The shell in which to execute make rules.
SHELL = /bin/sh

# The CMake executable.
CMAKE_COMMAND = /usr/bin/cmake

# The command to remove a file.
RM = /usr/bin/cmake -E remove -f

# Escaping for special characters.
EQUALS = =

# The top-level source directory on which CMake was run.
CMAKE_SOURCE_DIR = /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger

# The top-level build directory on which CMake was run.
CMAKE_BINARY_DIR = /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger/cmake-build-debug

# Include any dependencies generated for this target.
include CMakeFiles/uoj_judger.dir/depend.make

# Include the progress variables for this target.
include CMakeFiles/uoj_judger.dir/progress.make

# Include the compile flags for this target's objects.
include CMakeFiles/uoj_judger.dir/flags.make

CMakeFiles/uoj_judger.dir/main_judger.cpp.o: CMakeFiles/uoj_judger.dir/flags.make
CMakeFiles/uoj_judger.dir/main_judger.cpp.o: ../main_judger.cpp
	@$(CMAKE_COMMAND) -E cmake_echo_color --switch=$(COLOR) --green --progress-dir=/home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger/cmake-build-debug/CMakeFiles --progress-num=$(CMAKE_PROGRESS_1) "Building CXX object CMakeFiles/uoj_judger.dir/main_judger.cpp.o"
	/usr/bin/c++   $(CXX_DEFINES) $(CXX_INCLUDES) $(CXX_FLAGS) -o CMakeFiles/uoj_judger.dir/main_judger.cpp.o -c /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger/main_judger.cpp

CMakeFiles/uoj_judger.dir/main_judger.cpp.i: cmake_force
	@$(CMAKE_COMMAND) -E cmake_echo_color --switch=$(COLOR) --green "Preprocessing CXX source to CMakeFiles/uoj_judger.dir/main_judger.cpp.i"
	/usr/bin/c++  $(CXX_DEFINES) $(CXX_INCLUDES) $(CXX_FLAGS) -E /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger/main_judger.cpp > CMakeFiles/uoj_judger.dir/main_judger.cpp.i

CMakeFiles/uoj_judger.dir/main_judger.cpp.s: cmake_force
	@$(CMAKE_COMMAND) -E cmake_echo_color --switch=$(COLOR) --green "Compiling CXX source to assembly CMakeFiles/uoj_judger.dir/main_judger.cpp.s"
	/usr/bin/c++  $(CXX_DEFINES) $(CXX_INCLUDES) $(CXX_FLAGS) -S /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger/main_judger.cpp -o CMakeFiles/uoj_judger.dir/main_judger.cpp.s

CMakeFiles/uoj_judger.dir/main_judger.cpp.o.requires:

.PHONY : CMakeFiles/uoj_judger.dir/main_judger.cpp.o.requires

CMakeFiles/uoj_judger.dir/main_judger.cpp.o.provides: CMakeFiles/uoj_judger.dir/main_judger.cpp.o.requires
	$(MAKE) -f CMakeFiles/uoj_judger.dir/build.make CMakeFiles/uoj_judger.dir/main_judger.cpp.o.provides.build
.PHONY : CMakeFiles/uoj_judger.dir/main_judger.cpp.o.provides

CMakeFiles/uoj_judger.dir/main_judger.cpp.o.provides.build: CMakeFiles/uoj_judger.dir/main_judger.cpp.o


# Object files for target uoj_judger
uoj_judger_OBJECTS = \
"CMakeFiles/uoj_judger.dir/main_judger.cpp.o"

# External object files for target uoj_judger
uoj_judger_EXTERNAL_OBJECTS =

uoj_judger: CMakeFiles/uoj_judger.dir/main_judger.cpp.o
uoj_judger: CMakeFiles/uoj_judger.dir/build.make
uoj_judger: CMakeFiles/uoj_judger.dir/link.txt
	@$(CMAKE_COMMAND) -E cmake_echo_color --switch=$(COLOR) --green --bold --progress-dir=/home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger/cmake-build-debug/CMakeFiles --progress-num=$(CMAKE_PROGRESS_2) "Linking CXX executable uoj_judger"
	$(CMAKE_COMMAND) -E cmake_link_script CMakeFiles/uoj_judger.dir/link.txt --verbose=$(VERBOSE)

# Rule to build all files generated by this target.
CMakeFiles/uoj_judger.dir/build: uoj_judger

.PHONY : CMakeFiles/uoj_judger.dir/build

CMakeFiles/uoj_judger.dir/requires: CMakeFiles/uoj_judger.dir/main_judger.cpp.o.requires

.PHONY : CMakeFiles/uoj_judger.dir/requires

CMakeFiles/uoj_judger.dir/clean:
	$(CMAKE_COMMAND) -P CMakeFiles/uoj_judger.dir/cmake_clean.cmake
.PHONY : CMakeFiles/uoj_judger.dir/clean

CMakeFiles/uoj_judger.dir/depend:
	cd /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger/cmake-build-debug && $(CMAKE_COMMAND) -E cmake_depends "Unix Makefiles" /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger/cmake-build-debug /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger/cmake-build-debug /home/yaozh16/Project/uoj_thuoop/judge_client/1/uoj_judger/cmake-build-debug/CMakeFiles/uoj_judger.dir/DependInfo.cmake --color=$(COLOR)
.PHONY : CMakeFiles/uoj_judger.dir/depend

