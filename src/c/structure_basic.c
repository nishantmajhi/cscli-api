/* Unit 8: Structure and Union
 * Introduction to structures: defining, declaring, initializing, and
 * accessing members. */
#include <stdio.h>

struct Student {
    char name[50];
    int roll;
    float marks;
};

int main(void) {
    struct Student s1 = {"Aarav", 1, 87.5};

    printf("Name  : %s\n", s1.name);
    printf("Roll  : %d\n", s1.roll);
    printf("Marks : %.2f\n", s1.marks);

    return 0;
}
