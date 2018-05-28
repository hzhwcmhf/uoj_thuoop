cmake_minimum_required(VERSION 3.5)
project(uoj_judger)
include_directories(${PROJECT_SOURCE_DIR}/include)

set(CMAKE_CXX_STANDARD 11)

add_executable(uoj_judger main_judger.cpp include/uoj_judger.h include/uoj_work_path.h)

add_executable(run_program ${PROJECT_SOURCE_DIR}/run/run_program.cpp ${PROJECT_SOURCE_DIR}/run/run_program_conf.h)